@extends('layouts.app')

@section('title', $session->title ?? 'Chat Session')

@section('content')
<div class="page-width px-4 py-12 sm:px-6 md:px-8">
    <div class="mb-8">
        <a href="{{ route('agentic-chat.sessions.index') }}" class="text-sm text-body hover:text-ink">← Kembali ke daftar</a>
    </div>

    <h1 class="section-title mb-2">{{ $session->title ?? 'Untitled Session' }}</h1>
    <p class="text-sm text-muted mb-10">{{ $session->created_at->format('d M Y, H:i') }}</p>

    <div class="space-y-6">
        @foreach($session->messages as $message)
            <div class="rounded-card p-5 @if($message->role === 'user') bg-surface @elseif($message->role === 'assistant') bg-elevated @else bg-fog @endif">
                <div class="mb-2 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full border border-hairline px-2.5 py-0.5 text-xs font-medium text-body">
                        {{ ucfirst($message->role) }}
                    </span>
                    <span class="text-xs text-muted">{{ $message->created_at->format('H:i:s') }}</span>
                </div>

                <div class="text-sm leading-relaxed whitespace-pre-line">{{ $message->content }}</div>

                @if($message->toolExecutionLogs->isNotEmpty())
                    <div class="mt-4 space-y-3">
                        @foreach($message->toolExecutionLogs as $log)
                            <div class="rounded-2xl border border-hairline p-4">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="font-medium">Tool · {{ $log->tool_name }}</span>
                                    <span class="rounded-full border border-hairline px-2 py-0.5 text-body">
                                        {{ $log->status }}
                                    </span>
                                    @if($log->duration_ms)
                                        <span class="text-muted">{{ $log->duration_ms }}ms</span>
                                    @endif
                                    @if($log->loggable)
                                        <span class="text-muted">via {{ class_basename($log->loggable) }}</span>
                                    @endif
                                </div>

                                @if($log->input)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs text-body hover:text-ink">Input</summary>
                                        <pre class="mt-1 overflow-x-auto rounded-lg bg-surface p-3 text-xs">{{ json_encode($log->input, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @endif

                                @if($log->result)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs text-body hover:text-ink">Result</summary>
                                        <pre class="mt-1 overflow-x-auto rounded-lg bg-surface p-3 text-xs">{{ json_encode($log->result, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @endif

                                @if($log->error_message)
                                    <p class="mt-2 text-xs text-red-600">Error: {{ $log->error_message }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
