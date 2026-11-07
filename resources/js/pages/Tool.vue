<template>
    <div>
        <Head :title="__('Gen BI')" />

        <div class="flex items-center justify-between mb-6">
            <Heading class="">{{ __('Gen BI') }}</Heading>
        </div>

        <Card class="genbi">
            <!-- Conversation -->
            <div v-if="visibleMessages.length" ref="transcript" class="genbi__transcript">
                <div
                    v-for="message in visibleMessages"
                    :key="message.id"
                    class="genbi__message"
                    :class="`genbi__message--${message.role}`"
                >
                    <span class="genbi__bubble">{{ message.text }}</span>
                </div>
            </div>
            <div v-else class="genbi__empty">
                {{ __('Ask for a report or a dashboard metric in natural language, e.g. “a report of inactive customers” or “new users per month as a trend”.') }}
            </div>

            <!-- Proposals -->
            <div v-if="proposals.length" class="genbi__proposals">
                <div
                    v-for="proposal in proposals"
                    :key="proposal.key"
                    class="genbi__proposal"
                >
                    <div class="genbi__proposal-head">
                        <span class="genbi__badge" :class="`genbi__badge--${proposal.kind}`">
                            {{ proposal.kind === 'report' ? __('Report') : __('Metric') }}
                        </span>
                        <span class="genbi__proposal-title">{{ proposal.label }}</span>
                        <span v-if="proposal.meta" class="genbi__proposal-meta">{{ proposal.meta }}</span>
                    </div>

                    <p v-if="proposal.description" class="genbi__proposal-desc">{{ proposal.description }}</p>

                    <div v-if="proposal.saved" class="genbi__saved">
                        {{ __('Saved.') }}
                        <a :href="proposal.saved.url" class="genbi__link">{{ __('Open') }} “{{ proposal.saved.label }}”</a>
                    </div>
                    <div v-else-if="proposal.errorText" class="genbi__status genbi__status--error">
                        {{ proposal.errorText }}
                    </div>
                    <div v-else class="genbi__proposal-actions">
                        <button
                            type="button"
                            class="genbi__button genbi__button--accept"
                            :disabled="proposal.saving"
                            @click="accept(proposal)"
                        >
                            {{ proposal.saving ? __('Saving…') : __('Accept & save') }}
                        </button>
                        <button
                            type="button"
                            class="genbi__button"
                            :disabled="proposal.saving"
                            @click="discard(proposal)"
                        >
                            {{ __('Discard') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div v-if="isStreaming" class="genbi__status">
                <span class="genbi__spinner" />
                <span>{{ __('Thinking…') }}</span>
                <button type="button" class="genbi__link genbi__link--muted" @click="stop()">
                    {{ __('Stop') }}
                </button>
            </div>
            <div v-else-if="error" class="genbi__status genbi__status--error">
                <span>{{ __('The AI service is busy or overloaded right now. Please try again in a few minutes.') }}</span>
                <button v-if="lastText" type="button" class="genbi__link" @click="retry">
                    {{ __('Retry') }}
                </button>
            </div>

            <!-- Composer -->
            <div class="genbi__composer">
                <input
                    v-model="draft"
                    type="text"
                    class="w-full form-control form-input form-control-bordered genbi__input"
                    :placeholder="__('Describe the report or metric you want…')"
                    :disabled="isStreaming"
                    dusk="genbi-input"
                    @keydown.enter.prevent="send"
                />
                <button
                    type="button"
                    class="genbi__button genbi__button--accept"
                    :disabled="isStreaming || !draft.trim()"
                    dusk="genbi-send"
                    @click="send"
                >
                    {{ __('Send') }}
                </button>
            </div>
        </Card>
    </div>
</template>

<script>
import { useChat } from '@ai-sdk/vue'
import { DefaultChatTransport } from 'ai'
import { computed, ref } from 'vue'

export default {
    setup() {
        const conversationId = ref(null)

        const transport = new DefaultChatTransport({
            api: '/nova-vendor/nova-genbi/ai/chat',
            headers: () => ({
                'X-CSRF-TOKEN':
                    document.head.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            }),
            // The server creates the conversation and announces its id in a
            // header before the stream starts.
            fetch: async (url, options) => {
                const response = await window.fetch(url, options)
                const id = response.headers.get('X-Conversation-Id')

                if (id) {
                    conversationId.value = id
                }

                return response
            },
        })

        const chat = useChat(() => ({ transport }))

        const visibleMessages = computed(() =>
            chat.messages.value
                .map((message) => ({
                    id: message.id,
                    role: message.role,
                    text: (message.parts ?? [])
                        .filter((part) => part.type === 'text')
                        .map((part) => part.text)
                        .join('\n')
                        .trim(),
                }))
                .filter((message) => message.text !== '')
        )

        return {
            conversationId,
            chatMessages: chat.messages,
            chatStatus: chat.status,
            error: chat.error,
            sendMessage: chat.sendMessage,
            stop: chat.stop,
            visibleMessages,
        }
    },

    data: () => ({
        draft: '',
        // Last user text sent, kept so the error state can offer a one-click retry.
        lastText: '',
        // key -> { saving, saved, errorText } tracked outside the stream.
        resolved: {},
    }),

    computed: {
        isStreaming() {
            return ['submitted', 'streaming'].includes(this.chatStatus)
        },

        /**
         * Proposals come from the proposeReport / proposeMetric tool
         * invocations streamed as tool-input-available parts — typed data,
         * never parsed prose.
         */
        proposals() {
            const byKey = new Map()

            for (const message of this.chatMessages) {
                for (const part of message.parts ?? []) {
                    const kind = this.proposalKind(part)

                    if (!kind) {
                        continue
                    }

                    const input = part.input ?? {}
                    const state = this.resolved[part.toolCallId] ?? {}

                    const paramLabels = (input.parameters ?? [])
                        .map((p) => p.label || p.name)
                        .filter(Boolean)

                    byKey.set(part.toolCallId, {
                        key: part.toolCallId,
                        kind,
                        label: kind === 'report' ? input.name ?? '' : input.title ?? '',
                        description:
                            kind === 'report'
                                ? input.description ?? ''
                                : this.metricDescription(input.type),
                        meta:
                            kind === 'report'
                                ? paramLabels.length
                                    ? `${this.__('Asks for')}: ${paramLabels.join(', ')}`
                                    : ''
                                : input.dashboard
                                  ? `${this.__('Dashboard')}: ${input.dashboard}`
                                  : '',
                        input,
                        discarded: state.discarded === true,
                        saving: state.saving === true,
                        saved: state.saved ?? null,
                        errorText: state.errorText ?? null,
                    })
                }
            }

            return [...byKey.values()].filter((proposal) => !proposal.discarded)
        },
    },

    methods: {
        metricDescription(type) {
            const map = {
                value: this.__('A single key number.'),
                trend: this.__('A trend over time.'),
                partition: this.__('A breakdown by category.'),
                progress: this.__('Progress toward a target.'),
                table: this.__('A table of results.'),
            }

            return map[type] ?? ''
        },

        proposalKind(part) {
            if (part.type === 'tool-proposeReport') return 'report'
            if (part.type === 'tool-proposeMetric') return 'metric'

            if (part.type === 'dynamic-tool') {
                if (part.toolName === 'proposeReport') return 'report'
                if (part.toolName === 'proposeMetric') return 'metric'
            }

            return null
        },

        send() {
            const text = this.draft.trim()

            if (!text || this.isStreaming) {
                return
            }

            this.draft = ''
            this.lastText = text

            this.sendMessage(
                { text },
                { body: { conversationId: this.conversationId } }
            )
        },

        retry() {
            if (!this.lastText || this.isStreaming) {
                return
            }

            this.sendMessage(
                { text: this.lastText },
                { body: { conversationId: this.conversationId } }
            )
        },

        patch(key, changes) {
            this.resolved = { ...this.resolved, [key]: { ...(this.resolved[key] ?? {}), ...changes } }
        },

        async accept(proposal) {
            this.patch(proposal.key, { saving: true, errorText: null })

            const payload =
                proposal.kind === 'report'
                    ? {
                          kind: 'report',
                          name: proposal.input.name,
                          description: proposal.input.description,
                          sql: proposal.input.sql,
                          parameters: proposal.input.parameters ?? [],
                      }
                    : {
                          kind: 'metric',
                          title: proposal.input.title,
                          type: proposal.input.type,
                          sql: proposal.input.sql,
                          dashboard: proposal.input.dashboard,
                      }

            try {
                const { data } = await Nova.request().post('/nova-vendor/nova-genbi/persist', payload)
                this.patch(proposal.key, { saving: false, saved: { url: data.url, label: data.label } })
                Nova.success(this.__('Saved “:label”.', { label: data.label }))
            } catch (e) {
                const message = e?.response?.data?.message ?? this.__('Could not save the proposal.')
                this.patch(proposal.key, { saving: false, errorText: message })
                Nova.error(message)
            }
        },

        discard(proposal) {
            this.patch(proposal.key, { discarded: true })
        },
    },
}
</script>
