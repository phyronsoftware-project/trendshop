<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateChatMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $administrator = $request->user('admin');
        $conversations = $this->conversationQuery()->limit(100)->get();
        $requestedConversationId = $request->integer('conversation');
        $selectedConversation = $requestedConversationId > 0
            ? $conversations->firstWhere('id', $requestedConversationId)
            : $conversations->first();
        $messages = collect();

        if ($selectedConversation !== null) {
            $this->markCustomerMessagesRead($selectedConversation);
            $messages = $this->latestMessages($selectedConversation);
        }

        return view('admin.messages.index', compact('administrator', 'conversations', 'messages', 'selectedConversation'));
    }

    public function conversations(Request $request): JsonResponse
    {
        $activeConversationId = $request->integer('active_conversation');
        $conversations = $this->conversationQuery()->limit(100)->get();

        return response()->json([
            'html' => view('admin.messages._conversation-list', compact('activeConversationId', 'conversations'))->render(),
            'first_conversation_id' => $conversations->first()?->id,
        ]);
    }

    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $validated = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $this->markCustomerMessagesRead($conversation);
        $messages = $conversation->messages()
            ->with('sender:id,name,role')
            ->where('id', '>', $validated['after_id'] ?? 0)
            ->oldest('id')
            ->limit(100)
            ->get();
        $conversation->loadMissing('user.socialAccounts:id,user_id,provider_avatar_url');

        return response()->json([
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $this->messagePayload($messages),
        ]);
    }

    public function store(StoreChatMessageRequest $request, ChatConversation $conversation, CreateChatMessage $createChatMessage): JsonResponse
    {
        $administrator = $request->user('admin');
        // Preserve the single-file API while accepting multiple images from the composer.
        $attachments = array_filter([
            $request->file('attachment'),
            ...$request->file('attachments', []),
        ]);
        $messages = $createChatMessage->handleMany($conversation, $administrator, $request->validated(), $attachments);
        $messagePayload = $this->messagePayload($messages);

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh(['user.socialAccounts:id,user_id,provider_avatar_url'])),
            'message' => $messagePayload->first(),
            'messages' => $messagePayload,
        ], 201);
    }

    /** Build the ordered inbox query with sender details and unread totals. */
    private function conversationQuery(): Builder
    {
        return ChatConversation::query()
            ->with([
                'user:id,name,email,profile_image_path',
                'user.socialAccounts:id,user_id,provider_avatar_url',
                // Qualify latest-message columns because latestOfMany joins the same key name.
                'latestMessage' => fn (HasOne $query) => $query->select([
                    'chat_messages.id',
                    'chat_messages.conversation_id',
                    'chat_messages.sender_id',
                    'chat_messages.type',
                    'chat_messages.body',
                    'chat_messages.created_at',
                ]),
                'latestMessage.sender:id,name,role',
            ])
            ->withCount([
                'messages as unread_messages_count' => fn (Builder $query) => $query
                    ->whereNull('read_at')
                    ->whereHas('sender', fn (Builder $senderQuery) => $senderQuery->where('role', 'customer')),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    /** Load the latest bounded message history for the active admin thread. */
    private function latestMessages(ChatConversation $conversation): Collection
    {
        return $conversation->messages()
            ->with('sender:id,name,role')
            ->latest('id')
            ->limit(100)
            ->get()
            ->sortBy('id')
            ->values();
    }

    /** Mark only customer messages read when an administrator views a thread. */
    private function markCustomerMessagesRead(ChatConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_id', $conversation->user_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Return the active customer details needed by the admin chat header. */
    private function conversationPayload(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'user_name' => $conversation->user->name,
            'user_email' => $conversation->user->email,
            'avatar_url' => $conversation->user->avatarUrl(),
        ];
    }

    /** Render support-side message markup for incremental browser updates. */
    private function messagePayload(Collection $messages): Collection
    {
        return $messages->map(fn (ChatMessage $message): array => [
            'id' => $message->id,
            'html' => view('components.chat.message', [
                'message' => $message,
                'mine' => $message->sender?->role === 'admin',
            ])->render(),
        ]);
    }
}
