<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChatAttachmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ChatMessage $message): BinaryFileResponse
    {
        $message->loadMissing('conversation:id,user_id');
        $customer = $request->user('web');
        $administrator = $request->user('admin');
        $canViewAttachment = ($customer !== null && $message->conversation->user_id === $customer->id)
            || ($administrator?->role === 'admin' && $administrator->status === 'active');

        abort_unless($canViewAttachment && filled($message->attachment_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($message->attachment_path), 404);
        $attachmentName = preg_replace('/[^A-Za-z0-9._-]/', '_', Str::ascii($message->attachment_name ?? 'attachment')) ?: 'attachment';

        // Display authorized media inline while preventing MIME sniffing.
        return response()->file($disk->path($message->attachment_path), [
            'Content-Type' => $message->attachment_mime,
            'Content-Disposition' => 'inline; filename="'.$attachmentName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
