@extends('layouts.app')

@section('title', 'Agentic Chat Sessions')

@section('content')
<div class="page-width px-4 py-12 sm:px-6 md:px-8">
    <h1 class="section-title mb-8">Chat Sessions</h1>

    @if($sessions->isEmpty())
        <p class="text-body">Belum ada sesi chat. Data demo dapat di-generate melalui seeder.</p>
    @else
        <div class="grid gap-4">
            @foreach($sessions as $session)
                <a href="{{ route('agentic-chat.sessions.show', $session->id) }}"
                   class="block rounded-card bg-surface p-6 transition-colors hover:bg-fog">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-medium">{{ $session->title ?? 'Untitled Session' }}</h2>
                            <p class="mt-1 text-sm text-body">
                                {{ $session->messages_count }} pesan · {{ $session->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="text-body">→</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    @if(app()->isLocal() && $sessions->isNotEmpty())
        <div class="mt-8 rounded-2xl border border-hairline p-4">
            <p class="text-sm text-muted">
                <strong>Dev mode:</strong> Tambahkan <code>/naive</code> di akhir URL detail session untuk melihat mode tanpa eager loading (N+1 query demo).
            </p>
        </div>
    @endif
</div>
@endsection
