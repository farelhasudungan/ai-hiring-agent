<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = ChatSession::query()
            ->whereBelongsTo($request->user())
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();

        return view('agentic-chat.index', compact('sessions'));
    }

    public function show(Request $request, int $id): View
    {
        $session = ChatSession::query()
            ->whereBelongsTo($request->user())
            ->with([
                'messages' => fn ($query) => $query
                    ->orderBy('created_at')
                    ->orderBy('id'),
                'messages.toolExecutionLogs.loggable',
            ])
            ->findOrFail($id);

        return view('agentic-chat.show', compact('session'));
    }

    public function showNaive(Request $request, int $id): View
    {
        abort_unless(app()->isLocal(), 404);

        $session = ChatSession::query()
            ->whereBelongsTo($request->user())
            ->findOrFail($id);

        // This route intentionally demonstrates N+1 queries. Temporarily disable
        // the local lazy-loading guard only while materializing this demo view.
        Model::preventLazyLoading(false);

        try {
            foreach ($session->messages as $message) {
                foreach ($message->toolExecutionLogs as $log) {
                    $log->loggable;
                }
            }
        } finally {
            Model::preventLazyLoading(! app()->isProduction());
        }

        return view('agentic-chat.show', compact('session'));
    }
}
