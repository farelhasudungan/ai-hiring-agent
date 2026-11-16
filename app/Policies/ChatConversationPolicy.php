<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

class ChatConversationPolicy
{
    /**
     * Roles allowed to search candidates through the AI chat.
     *
     * @var list<string>
     */
    private const CHAT_ROLES = ['admin', 'hr'];

    public function viewAny(User $user): bool
    {
        return $this->canChat($user);
    }

    public function create(User $user): bool
    {
        return $this->canChat($user);
    }

    /**
     * Conversations are private to the user who started them.
     */
    public function view(User $user, ChatConversation $conversation): bool
    {
        return $this->canChat($user)
            && $user->id === $conversation->user_id
            && $user->company_id === $conversation->company_id;
    }

    private function canChat(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, self::CHAT_ROLES, true);
    }
}
