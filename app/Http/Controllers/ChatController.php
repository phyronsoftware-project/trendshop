<?php

namespace App\Http\Controllers;

use App\Actions\CreateChatMessage;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user();
        $conversation = ChatConversation::query()->whereBelongsTo($customer)->first();
        $messages = collect();

        if ($conversation !== null) {
            $this->markAdministratorMessagesRead($conversation, $customer);
            $messages = $this->latestMessages($conversation);
        }

        return view('pages.contact', compact('conversation', 'customer', 'messages'));
    }

    public function messages(Request $request): JsonResponse
    {
        $validated = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $customer = $request->user();
        $conversation = ChatConversation::query()->whereBelongsTo($customer)->first();

        if ($conversation === null) {
            return response()->json(['conversation_id' => null, 'messages' => []]);
        }

        $this->markAdministratorMessagesRead($conversation, $customer);
        $messages = $conversation->messages()
            ->with('sender:id,name,role')
            ->where('id', '>', $validated['after_id'] ?? 0)
            ->oldest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $this->messagePayload($messages, $customer),
        ]);
    }

    public function store(StoreChatMessageRequest $request, CreateChatMessage $createChatMessage): JsonResponse
    {
        $customer = $request->user();
        $conversation = ChatConversation::query()->firstOrCreate(
            ['user_id' => $customer->id],
            ['status' => 'open'],
        );
        // Preserve the single-file API while accepting multiple images from the composer.
        $attachments = array_filter([
            $request->file('attachment'),
            ...$request->file('attachments', []),
        ]);
        $messages = $createChatMessage->handleMany($conversation, $customer, $request->validated(), $attachments);
        $messagePayload = $this->messagePayload($messages, $customer);

        return response()->json([
            'conversation_id' => $conversation->id,
            'message' => $messagePayload->first(),
            'messages' => $messagePayload,
        ], 201);
    }

    /** Load only the newest messages needed for the initial conversation view. */
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

    /** Mark support replies as read when the customer opens or polls the thread. */
    private function markAdministratorMessagesRead(ChatConversation $conversation, User $customer): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $customer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Render escaped message markup for safe incremental browser updates. */
    private function messagePayload(Collection $messages, User $customer): Collection
    {
        return $messages->map(fn (ChatMessage $message): array => [
            'id' => $message->id,
            'html' => view('components.chat.message', [
                'message' => $message,
                'mine' => $message->sender_id === $customer->id,
            ])->render(),
        ]);
    }
}
