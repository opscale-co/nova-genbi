<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Opscale\NovaGenbi\AI\Agents\GenBiAgent;
use Opscale\NovaGenbi\Contracts\DomainResolver;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streaming chat endpoint behind Nova's panel auth.
 *
 * It resolves the single GenBi agent server-side, injects the database schema
 * (DBML from the bound DomainResolver) and streams the reply using the Vercel
 * UI Message Stream v1 protocol that @ai-sdk/vue consumes. The client never
 * names the agent class or supplies the schema.
 */
final class ChatController
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $prompt = $this->prompt($request);

        abort_if($prompt === '', 422, 'The request carries no user message.');

        $conversationId = $this->resolveConversation($request, $user, $prompt);

        $schema = resolve(DomainResolver::class)->resolve();

        $provider = config('nova-genbi.ai.provider');
        $model = config('nova-genbi.ai.model');
        $timeout = is_numeric(config('nova-genbi.ai.max_duration')) ? (int) config('nova-genbi.ai.max_duration') : 120;

        /** @var GenBiAgent $agent */
        $agent = resolve(GenBiAgent::class)
            ->withSchema(is_string($schema) ? $schema : '')
            ->continue($conversationId, as: $user);

        $response = $agent
            ->stream(
                $prompt,
                provider: is_string($provider) && $provider !== '' ? $provider : null,
                model: is_string($model) && $model !== '' ? $model : null,
                timeout: $timeout,
            )
            ->usingVercelDataProtocol()
            ->toResponse($request);

        // Without this, nginx buffers the whole SSE stream and nothing is
        // visible until the response ends.
        $response->headers->set('X-Accel-Buffering', 'no');

        // The client stores it and sends it back so the conversation stays a
        // single thread per chat session.
        $response->headers->set('X-Conversation-Id', $conversationId);

        return $response;
    }

    /**
     * Extract the text of the latest user message from the useChat payload
     * (UIMessages carry their text inside typed parts).
     */
    private function prompt(Request $request): string
    {
        $lastUserMessage = collect((array) $request->input('messages', []))
            ->last(static fn (mixed $message): bool => is_array($message) && ($message['role'] ?? null) === 'user');

        return collect((array) ($lastUserMessage['parts'] ?? []))
            ->filter(static fn (mixed $part): bool => is_array($part) && ($part['type'] ?? null) === 'text')
            ->pluck('text')
            ->implode("\n");
    }

    /**
     * Continue the conversation the client references — only if it belongs to
     * the authenticated user — or start a new one and hand its ID back.
     */
    private function resolveConversation(Request $request, Authenticatable $authenticatable, string $prompt): string
    {
        $conversationId = (string) $request->input('conversationId', '');

        if ($conversationId === '') {
            return resolve(ConversationStore::class)->storeConversation(
                $authenticatable->getAuthIdentifier(),
                Str::limit($prompt, 50, preserveWords: true)
            );
        }

        $owned = DB::connection(config('ai.conversations.connection'))
            ->table((string) config('ai.conversations.tables.conversations', 'agent_conversations'))
            ->where('id', $conversationId)
            ->where('user_id', $authenticatable->getAuthIdentifier())
            ->exists();

        abort_unless($owned, 403, 'That conversation belongs to another user.');

        return $conversationId;
    }
}
