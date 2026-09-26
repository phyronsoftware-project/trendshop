<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerChatTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('chat_conversations') || ! Schema::hasTable('chat_messages')) {
            $this->markTestSkipped('Apply the TrendShop chat SQL before running chat tests.');
        }
    }

    public function test_guest_is_redirected_to_login_from_customer_chat(): void
    {
        $this->get(route('chat.index'))->assertRedirect(route('login'));
    }

    public function test_customer_can_open_chat_and_send_a_text_message(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer)->get(route('chat.index'))->assertSee('TrendShop Support');
        $response = $this->actingAs($customer)->postJson(route('chat.messages.store'), ['body' => 'Can you help with this product?']);

        $response->assertCreated()->assertJsonPath('message.id', fn ($id): bool => is_int($id));
        $this->assertDatabaseHas('chat_conversations', ['user_id' => $customer->id, 'status' => 'open']);
        $this->assertDatabaseHas('chat_messages', ['sender_id' => $customer->id, 'type' => 'text', 'body' => 'Can you help with this product?']);
    }

    public function test_customer_message_requires_text_or_an_attachment(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $response = $this->actingAs($customer)->postJson(route('chat.messages.store'), []);

        $response->assertUnprocessable()->assertJsonValidationErrorFor('body');
        $this->assertDatabaseMissing('chat_conversations', ['user_id' => $customer->id]);
    }

    public function test_customer_can_upload_multiple_images_to_private_chat_storage(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $images = [
            UploadedFile::fake()->image('product-front.jpg')->size(500),
            UploadedFile::fake()->image('product-back.jpg')->size(500),
        ];

        $response = $this->actingAs($customer)->postJson(route('chat.messages.store'), ['attachments' => $images]);

        $response->assertCreated()->assertJsonCount(2, 'messages');
        $messages = ChatMessage::query()->where('sender_id', $customer->id)->get();
        $this->assertCount(2, $messages);
        $messages->each(function (ChatMessage $message): void {
            $this->assertSame('image', $message->type);
            Storage::disk('local')->assertExists($message->attachment_path);
        });
    }

    public function test_customer_cannot_view_another_customers_private_attachment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $otherCustomer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $conversation = ChatConversation::factory()->create(['user_id' => $owner->id]);
        Storage::disk('local')->put('chat/private-image.jpg', 'image-content');
        $message = ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $owner->id,
            'type' => 'image',
            'body' => null,
            'attachment_path' => 'chat/private-image.jpg',
            'attachment_name' => 'private-image.jpg',
            'attachment_mime' => 'image/jpeg',
            'attachment_size' => 13,
        ]);

        $this->actingAs($otherCustomer)->get(route('chat.attachments.show', $message))->assertNotFound();
    }
}
