<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreChatMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/webm,video/webm'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'audio_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }

    /** Require useful content and keep image uploads within five megabytes. */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('body') && ! $this->hasFile('attachment') && ! $this->hasFile('attachments')) {
                    $validator->errors()->add('body', 'Enter a message or attach a file.');
                }

                $attachment = $this->file('attachment');

                if ($attachment !== null && str_starts_with((string) $attachment->getMimeType(), 'image/') && $attachment->getSize() > 5 * 1024 * 1024) {
                    $validator->errors()->add('attachment', 'Images may not be larger than 5 MB.');
                }
            },
        ];
    }

    /** Trim text without changing emoji or multilingual content. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->string('body')->toString())]);
        }
    }
}
