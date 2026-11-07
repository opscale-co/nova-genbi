<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\AI\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\RemembersConversations as RemembersConversationsContract;
use Laravel\Ai\Promptable;
use Opscale\NovaGenbi\AI\Tools\ProposeMetric;
use Opscale\NovaGenbi\AI\Tools\ProposeReport;
use Opscale\NovaGenbi\AI\Tools\RunQuery;

/**
 * The conversational analyst that turns natural-language requests into
 * read-only SQL and proposes dashboards (metric cards) and exportable reports.
 *
 * The chat endpoint injects the database schema (as DBML, from the bound
 * DomainResolver) before every turn via withSchema(). The agent never writes
 * to the database: it previews with runQuery and proposes with
 * proposeReport / proposeMetric, and the user is the one who persists.
 */
class GenBiAgent implements Agent, HasTools, RemembersConversationsContract
{
    use Promptable;
    use RemembersConversations;

    private string $schema = '';

    final public function withSchema(string $dbml): static
    {
        $this->schema = trim($dbml);

        return $this;
    }

    final public function tools(): iterable
    {
        yield resolve(RunQuery::class);
        yield new ProposeReport;
        yield new ProposeMetric;
    }

    final public function instructions(): string
    {
        return implode("\n\n", array_filter([
            $this->persona(),
            $this->schemaBlock(),
            $this->rules(),
        ]));
    }

    protected function persona(): string
    {
        return <<<'PROMPT'
        You are a senior business-intelligence analyst embedded in a Laravel Nova admin panel.
        You help the user explore their database and turn questions into two kinds of artifacts:
        - Reports: parametrized, read-only SQL the user runs and exports to Excel/CSV.
        - Metrics: read-only SQL rendered as native Nova dashboard cards (value, trend, partition, progress, table).
        PROMPT;
    }

    private function schemaBlock(): string
    {
        if ($this->schema === '') {
            return 'No database schema was provided. Ask the user to describe the relevant tables '
                . 'and columns before writing SQL, and never invent table or column names.';
        }

        return "Database schema (DBML — use ONLY these tables and columns; never invent names):\n" . $this->schema;
    }

    private function rules(): string
    {
        return <<<'PROMPT'
        How to work:
        1. Understand the goal with as few questions as possible. Derive table/column choices from the schema above.
        2. ALWAYS write read-only SQL: a single SELECT or WITH ... SELECT statement. Never INSERT/UPDATE/DELETE/DDL, never stacked statements. Dialect-portable SQL is preferred.
        3. Validate before proposing. Call runQuery with your SQL to confirm it is valid and returns the expected shape. Fix and retry on errors.
        4. Then propose, never persist. Call proposeReport or proposeMetric with the final spec. The user reviews each proposal and accepts or discards it; nothing is saved until they accept. After proposing, wait for their reaction.
        5. Reports use :named placeholders for runtime inputs, each backed by a matching parameter entry. Metrics take no placeholders.
        6. For metrics, pick the card type whose shape matches your SQL (see the proposeMetric type descriptions).
        7. Answer in the user's language, briefly. The proposals are the protagonist, not your prose.
        PROMPT;
    }
}
