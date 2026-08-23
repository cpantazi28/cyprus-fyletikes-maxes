<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Inbox.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $messages = Message::where('recipient_id', $user->id)
            ->where('is_deleted_by_recipient', false)
            ->with('sender')
            ->latest()
            ->paginate(20);

        return view('game.messages.index', [
            'messages' => $messages,
            'tab' => 'inbox',
        ]);
    }

    /**
     * Outbox (Sent).
     */
    public function sent(Request $request): View
    {
        $user = Auth::user();
        $messages = Message::where('sender_id', $user->id)
            ->where('is_deleted_by_sender', false)
            ->with('recipient')
            ->latest()
            ->paginate(20);

        return view('game.messages.sent', [
            'messages' => $messages,
            'tab' => 'sent',
        ]);
    }

    /**
     * Compose form.
     */
    public function create(Request $request): View
    {
        return view('game.messages.create', [
            'recipientName' => $request->query('to', ''),
            'replySubject' => $request->query('subject', ''),
        ]);
    }

    /**
     * Send message.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'recipient' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $recipientUser = User::where('name', $request->input('recipient'))->first();
        if (! $recipientUser) {
            return back()->withInput()->with('error', "Player '{$request->input('recipient')}' does not exist.");
        }

        $user = Auth::user();

        Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $recipientUser->id,
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'is_read' => false,
        ]);

        return redirect()->route('game.messages.sent')->with('success', 'Message sent successfully.');
    }

    /**
     * Read message.
     */
    public function show(Request $request, int $id): View|RedirectResponse
    {
        $user = Auth::user();
        $message = Message::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('recipient_id', $user->id)
                    ->orWhere('sender_id', $user->id);
            })
            ->with(['sender', 'recipient'])
            ->first();

        if (! $message) {
            return redirect()->route('game.messages.index')->with('error', 'Message not found.');
        }

        if ($message->recipient_id === $user->id && ! $message->is_read) {
            $message->is_read = true;
            $message->save();
        }

        return view('game.messages.show', [
            'message' => $message,
            'isRecipient' => $message->recipient_id === $user->id,
        ]);
    }

    /**
     * Delete message.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $message = Message::find($id);

        if ($message) {
            if ($message->recipient_id === $user->id) {
                $message->is_deleted_by_recipient = true;
            }
            if ($message->sender_id === $user->id) {
                $message->is_deleted_by_sender = true;
            }

            if ($message->is_deleted_by_sender && $message->is_deleted_by_recipient) {
                $message->delete();
            } else {
                $message->save();
            }
        }

        return back()->with('success', 'Message deleted.');
    }
}
