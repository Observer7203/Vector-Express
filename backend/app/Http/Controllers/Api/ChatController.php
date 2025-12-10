<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = Chat::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['order', 'latestMessage', 'participants.user'])
            ->latest('updated_at')
            ->paginate(15);

        // Add unread count for each chat
        $chats->getCollection()->transform(function ($chat) use ($user) {
            $chat->unread_count = $chat->unreadMessagesCount($user->id);
            return $chat;
        });

        return response()->json($chats);
    }

    public function show(Request $request, Chat $chat): JsonResponse
    {
        $this->authorize('view', $chat);

        $chat->load(['order.carrier.company', 'order.user', 'participants.user']);

        return response()->json([
            'chat' => $chat,
            'unread_count' => $chat->unreadMessagesCount($request->user()->id),
        ]);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        $this->authorize('view', $chat);

        $messages = $chat->messages()
            ->with(['sender', 'attachments'])
            ->latest()
            ->paginate(50);

        // Mark participant's messages as read
        $participant = $chat->participants()->where('user_id', $request->user()->id)->first();
        if ($participant) {
            $participant->markAsRead();
        }

        return response()->json($messages);
    }

    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $this->authorize('sendMessage', $chat);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $participant = $chat->participants()->where('user_id', $user->id)->first();

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => $participant->role,
            'sender_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $chat->touch(); // Update chat's updated_at

        // TODO: Broadcast message via WebSocket
        // TODO: Process AI response if needed

        return response()->json([
            'message' => $message->load(['sender', 'attachments']),
        ], 201);
    }

    public function sendAttachment(Request $request, Chat $chat): JsonResponse
    {
        $this->authorize('sendMessage', $chat);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'content' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $participant = $chat->participants()->where('user_id', $user->id)->first();

        // Create message
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => $participant->role,
            'sender_id' => $user->id,
            'content' => $request->content ?? '',
        ]);

        // Store and attach file
        $file = $request->file('file');
        $path = $file->store("chat-attachments/{$chat->id}", 'public');

        MessageAttachment::create([
            'message_id' => $message->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $chat->touch();

        return response()->json([
            'message' => $message->load(['sender', 'attachments']),
        ], 201);
    }

    public function markAsRead(Request $request, Chat $chat, Message $message): JsonResponse
    {
        $this->authorize('view', $chat);

        $participant = $chat->participants()->where('user_id', $request->user()->id)->first();
        if ($participant) {
            $participant->update(['last_read_at' => $message->created_at]);
        }

        return response()->json([
            'message' => 'Marked as read',
        ]);
    }
}
