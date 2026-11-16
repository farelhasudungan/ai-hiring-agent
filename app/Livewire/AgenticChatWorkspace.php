<?php

namespace App\Livewire;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Interactive Roku recruitment workspace for the Secure LLM Chat API Hub.
 *
 * Streaming itself is handled in the browser against the shared SSE endpoint
 * (POST /agentic-chat/stream); this component owns the persisted state.
 */
class AgenticChatWorkspace extends Component
{
    #[Url(as: 'session', except: null)]
    public ?int $sessionId = null;

    public function mount(): void
    {
        if ($this->sessionId !== null) {
            $this->sessionId = $this->ownedSession($this->sessionId)->id;
        }
    }

    public function selectSession(int $sessionId): void
    {
        $this->sessionId = $this->ownedSession($sessionId)->id;
    }

    public function newChat(): void
    {
        $this->sessionId = null;
    }

    /**
     * Called from the browser once a streamed answer has been persisted.
     */
    #[On('conversation-completed')]
    public function conversationCompleted(int $sessionId): void
    {
        $this->sessionId = $this->ownedSession($sessionId)->id;
    }

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $sessions = ChatSession::query()
            ->whereBelongsTo($user)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $session = $this->sessionId !== null
            ? ChatSession::query()->whereBelongsTo($user)->find($this->sessionId)
            : null;

        return view('livewire.agentic-chat-workspace', [
            'sessions' => $sessions,
            'session' => $session,
            'messages' => $session?->messages()->orderBy('id')->get() ?? collect(),
            'log' => $session?->chatLogs()->latest('id')->first(),
        ]);
    }

    protected function ownedSession(int $sessionId): ChatSession
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return ChatSession::query()
            ->whereBelongsTo($user)
            ->findOrFail($sessionId);
    }
}
