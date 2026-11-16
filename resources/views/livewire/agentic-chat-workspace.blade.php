@php
    $meta = [
        'model' => $log?->model_used,
        'provider' => $log?->provider,
        'prompt_tokens' => $log?->prompt_tokens,
        'completion_tokens' => $log?->completion_tokens,
        'total_tokens' => $log?->total_tokens,
    ];
@endphp

<div
    class="page-width px-4 py-8 sm:px-6 md:px-8"
    data-agentic-chat
    data-stream-url="{{ route('agentic-chat.stream') }}"
    data-session-id="{{ $session?->id }}"
>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Agentic Chat</p>
            <h1 class="mt-2 font-serif text-3xl tracking-[-0.015em] sm:text-4xl">Ruang kerja rekrutmen</h1>
        </div>
        <p class="max-w-sm text-sm leading-6 text-body">
            Asisten HR Roku membantu membaca CV, menyusun pertanyaan wawancara, dan merangkum kandidat.
            Rekomendasi AI bersifat <em>advisory</em> — keputusan tetap di tangan rekruter.
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">
        <aside class="rounded-card border border-hairline bg-fog p-3">
            <button type="button" wire:click="newChat" class="button-primary w-full" data-chat-new>
                <x-roku-icon name="sparkles" :size="18" />
                Obrolan baru
            </button>

            <nav class="mt-3 max-h-[45vh] space-y-1 overflow-y-auto lg:max-h-[calc(100dvh-18rem)]" aria-label="Riwayat obrolan">
                @forelse($sessions as $item)
                    <button
                        type="button"
                        wire:click="selectSession({{ $item->id }})"
                        wire:key="session-{{ $item->id }}"
                        data-chat-session
                        @class([
                            'flex w-full flex-col gap-1 rounded-2xl px-3 py-2.5 text-left transition-colors hover:bg-surface',
                            'bg-surface' => $session?->id === $item->id,
                        ])
                        @if($session?->id === $item->id) aria-current="true" @endif
                    >
                        <span class="truncate text-sm font-[450]">{{ $item->title ?: 'Obrolan tanpa judul' }}</span>
                        <span class="text-xs text-muted">
                            {{ $item->updated_at?->diffForHumans() }} · {{ $item->messages_count }} pesan
                        </span>
                    </button>
                @empty
                    <p class="px-3 py-4 text-sm leading-6 text-muted">
                        Belum ada obrolan. Mulai dengan pertanyaan pertama Anda.
                    </p>
                @endforelse
            </nav>
        </aside>

        <section
            class="flex min-h-[60vh] flex-col overflow-hidden rounded-card border border-hairline bg-elevated lg:h-[calc(100dvh-14rem)]"
            data-chat-panel
            aria-busy="false"
        >
            <header class="flex flex-wrap items-start justify-between gap-3 border-b border-hairline px-4 py-4 sm:px-6">
                <div class="min-w-0">
                    <h2 class="truncate font-serif text-2xl tracking-[-0.009em]">
                        {{ $session?->title ?: 'Obrolan baru' }}
                    </h2>
                    <p class="mt-1 text-xs text-muted">
                        System prompt rekrutmen Roku disuntikkan di sisi server.
                    </p>
                </div>

                <dl class="flex flex-wrap items-center gap-1.5" data-chat-meta aria-label="Metadata respons terakhir">
                    <div class="mini-tag">Model <span class="font-[450] text-ink" data-meta-model>{{ $meta['model'] ?? '—' }}</span></div>
                    <div class="mini-tag">Provider <span class="font-[450] text-ink" data-meta-provider>{{ $meta['provider'] ?? '—' }}</span></div>
                    <div class="mini-tag">Prompt <span class="font-[450] text-ink" data-meta-prompt-tokens>{{ $meta['prompt_tokens'] ?? '—' }}</span></div>
                    <div class="mini-tag">Completion <span class="font-[450] text-ink" data-meta-completion-tokens>{{ $meta['completion_tokens'] ?? '—' }}</span></div>
                    <div class="mini-tag">Total <span class="font-[450] text-ink" data-meta-total-tokens>{{ $meta['total_tokens'] ?? '—' }}</span></div>
                </dl>
            </header>

            <div
                class="flex-1 space-y-4 overflow-y-auto px-4 py-5 sm:px-6"
                data-chat-messages
                aria-live="polite"
                aria-relevant="additions text"
            >
                @if($messages->isEmpty())
                    <div class="mx-auto flex h-full max-w-md flex-col items-center justify-center gap-4 py-10 text-center">
                        <span class="icon-bubble">
                            <x-roku-icon name="sparkles" :size="20" />
                        </span>
                        <h3 class="font-serif text-2xl tracking-[-0.009em]">Mulai obrolan baru</h3>
                        <p class="text-sm leading-6 text-body">
                            Tanyakan apa pun seputar rekrutmen: membaca CV, menyusun pertanyaan wawancara,
                            atau membandingkan kandidat.
                        </p>
                        <div class="rounded-2xl bg-blush-peach px-4 py-3 text-left text-sm leading-6 text-sienna-brown">
                            Contoh: “Buat 3 pertanyaan wawancara teknis untuk Senior Backend Engineer.”
                        </div>
                    </div>
                @else
                    @foreach($messages as $message)
                        <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}" wire:key="message-{{ $message->id }}">
                            <div @class([
                                'max-w-[85%] rounded-card px-4 py-3 text-sm leading-relaxed break-words whitespace-pre-line',
                                'bg-ink text-canvas' => $message->role === 'user',
                                'bg-surface text-ink' => $message->role !== 'user',
                            ])>
                                @if($message->role !== 'user')
                                    <p class="mb-1 text-[10px] tracking-[0.12em] text-muted uppercase">Asisten Roku</p>
                                @endif
                                {{ $message->content }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <form class="border-t border-hairline px-4 py-4 sm:px-6" data-chat-form novalidate>
                <label for="chat-prompt" class="sr-only">Prompt untuk asisten rekrutmen</label>
                <textarea
                    id="chat-prompt"
                    name="prompt"
                    rows="1"
                    maxlength="1000"
                    data-chat-input
                    class="auth-input resize-none"
                    placeholder="Tanyakan sesuatu tentang kandidat, CV, atau proses rekrutmen…"
                ></textarea>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-muted">Enter untuk kirim · Shift+Enter untuk baris baru · maks. 1000 karakter</p>
                    <button type="submit" class="button-primary disabled:opacity-60" data-chat-send>
                        <span data-chat-send-label>Kirim</span>
                        <x-roku-icon name="arrow-up" :size="18" />
                    </button>
                </div>

                <p class="sr-only" data-chat-status aria-live="polite"></p>

                <div class="mt-3 hidden items-start justify-between gap-3 rounded-2xl border border-hairline bg-fog px-4 py-3" data-chat-error role="alert">
                    <p class="text-sm leading-6 text-ink" data-chat-error-message></p>
                    <button type="button" class="text-link shrink-0" data-chat-retry>Ulangi</button>
                </div>
            </form>
        </section>
    </div>

    <template data-chat-user-template>
        <div class="flex justify-end" data-chat-optimistic>
            <div class="max-w-[85%] rounded-card bg-ink px-4 py-3 text-sm leading-relaxed break-words whitespace-pre-line text-canvas" data-chat-user-text></div>
        </div>
    </template>

    <template data-chat-assistant-template>
        <div class="flex justify-start" data-chat-optimistic>
            <div class="max-w-[85%] rounded-card bg-surface px-4 py-3 text-sm leading-relaxed break-words whitespace-pre-line text-ink">
                <p class="mb-1 text-[10px] tracking-[0.12em] text-muted uppercase">Asisten Roku</p>
                <span data-chat-stream-text></span>
                <span class="inline-flex items-center gap-1 align-middle" data-chat-typing aria-hidden="true">
                    <span class="size-1.5 animate-bounce rounded-full bg-muted"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-muted [animation-delay:150ms]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-muted [animation-delay:300ms]"></span>
                </span>
            </div>
        </div>
    </template>
</div>
