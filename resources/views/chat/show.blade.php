<x-dashboard-layout :title="$conversation->title" flush>
    <x-chat-tabs :conversations="$conversations" :current="$conversation">
        <section class="flex min-w-0 flex-1 flex-col" aria-label="Conversation"
            data-chat
            data-chat-url="{{ route('chat.messages.store', $conversation) }}"
            data-chat-token="{{ csrf_token() }}"
            @if ($firstMessage) data-chat-first-message="{{ $firstMessage }}" @endif>
            <div class="border-b border-line/60 bg-paper px-4 py-2">
                <h1 class="truncate text-sm font-medium">{{ $conversation->title }}</h1>
                <p class="truncate text-xs text-muted">Searching in: {{ $conversation->job?->title ?? 'all job postings' }}</p>
            </div>

            <div data-chat-scroll class="flex-1 overflow-y-auto scroll-smooth">
                <div data-chat-messages class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8" aria-live="polite"></div>
            </div>

            <div class="bg-canvas px-4 pt-2 pb-4">
                <form data-chat-form class="mx-auto flex w-full max-w-3xl flex-col gap-2">
                    @unless ($hasAiProvider)
                        <p role="alert" class="rounded-xl border border-amber-700 bg-amber-50 px-4 py-3 text-sm text-amber-900">Set up an AI provider in <a href="{{ route('settings.ai.edit') }}" class="underline">AI settings</a> before using the chat.</p>
                    @endunless
                    <x-chat-composer :max-length="$maxLength" :disabled="! $hasAiProvider" placeholder="Ask a follow-up or refine the search…" />
                </form>
            </div>
        </section>

        <script type="application/json" id="chat-history">@json($messages, JSON_HEX_TAG | JSON_HEX_AMP)</script>
    </x-chat-tabs>
</x-dashboard-layout>
