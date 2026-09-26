<?php

namespace App\Actions;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CreateChatMessage
{
    /** Store one validated text, image or voice message atomically. */
    public function handle(ChatConversation $conversation, User $sender, array $data, ?UploadedFile $attachment): ChatMessage
    {
        return $this->handleMany($conversation, $sender, $data, array_filter([$attachment]))->firstOrFail();
    }

    /** Store text or multiple image attachments as an atomic message group. */
    public function handleMany(ChatConversation $conversation, User $sender, array $data, array $attachments): Collection
    {
        $storedAttachments = collect($attachments)->map(function (UploadedFile $attachment) use ($conversation): array {
            return [
                'file' => $attachment,
                'path' => $attachment->store('chat/'.$conversation->id, 'local'),
            ];
        });

        try {
            return DB::transaction(function () use ($conversation, $sender, $data, $storedAttachments): Collection {
                $attachmentRows = $storedAttachments->isEmpty() ? collect([null]) : $storedAttachments;
                $messages = $attachmentRows->values()->map(function (?array $storedAttachment, int $index) use ($conversation, $sender, $data): ChatMessage {
                    $attachment = $storedAttachment['file'] ?? null;
                    $mimeType = $attachment?->getMimeType();
                    $messageType = $attachment === null ? 'text' : (str_starts_with((string) $mimeType, 'image/') ? 'image' : 'audio');

                    return $conversation->messages()->create([
                        'sender_id' => $sender->id,
                        'type' => $messageType,
                        'body' => $index === 0 && filled($data['body'] ?? null) ? $data['body'] : null,
                        'attachment_path' => $storedAttachment['path'] ?? null,
                        'attachment_name' => $attachment === null ? null : Str::limit(basename($attachment->getClientOriginalName()), 255, ''),
                        'attachment_mime' => $mimeType,
                        'attachment_size' => $attachment?->getSize(),
                        'audio_duration_seconds' => $messageType === 'audio' ? ($data['audio_duration_seconds'] ?? null) : null,
                    ]);
                });

                // Assign an unclaimed conversation when its first administrator responds.
                $conversationUpdates = ['last_message_at' => now(), 'status' => 'open'];
                if ($sender->role === 'admin' && $conversation->assigned_admin_id === null) {
                    $conversationUpdates['assigned_admin_id'] = $sender->id;
                }
                $conversation->update($conversationUpdates);

                return $messages->each->load('sender:id,name,role');
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedAttachments->pluck('path')->all());

            throw $exception;
        }
    }
}
