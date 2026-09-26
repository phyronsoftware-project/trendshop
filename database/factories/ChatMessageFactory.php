<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'sender_id' => User::factory()->state(['role' => 'customer', 'status' => 'active']),
            'type' => 'text',
            'body' => fake()->sentence(),
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'audio_duration_seconds' => null,
            'read_at' => null,
        ];
    }
}
