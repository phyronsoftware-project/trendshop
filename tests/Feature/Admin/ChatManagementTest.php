<?php

namespace Tests\Feature\Admin;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('chat_conversations') || ! Schema::hasTable('chat_messages')) {
            $this->markTestSkipped('Apply the TrendShop chat SQL before running chat tests.');
        }
    }

    public function test_guest_is_redirected_to_admin_login_from_message_inbox(): void
    {
        $this->get(route('admin.chat.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customer_cannot_access_the_admin_message_inbox(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer)->get(route('admin.chat.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_customer_conversations_and_reply(): void
    {
        $administrator = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $conversation = ChatConversation::factory()->create(['user_id' => $customer->id, 'assigned_admin_id' => null]);
        ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'body' => 'Is this product available?',
        ]);

        $this->actingAs($administrator, 'admin')
            ->get(route('admin.chat.index', ['conversation' => $conversation->id]))
            ->assertSee($customer->name)
            ->assertSee('Is this product available?');

        $response = $this->actingAs($administrator, 'admin')->postJson(
            route('admin.chat.messages.store', $conversation),
            ['body' => 'Yes, it is available.'],
        );

        $response->assertCreated()->assertJsonPath('conversation.id', $conversation->id);
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $administrator->id,
            'body' => 'Yes, it is available.',
        ]);
        $this->assertSame($administrator->id, $conversation->fresh()->assigned_admin_id);
    }

    public function test_opening_admin_messages_marks_customer_messages_read(): void
    {
        $administrator = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $conversation = ChatConversation::factory()->create(['user_id' => $customer->id]);
        $message = ChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'read_at' => null,
        ]);

        $this->actingAs($administrator, 'admin')->getJson(route('admin.chat.messages.index', $conversation))->assertOk();

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_admin_can_send_multiple_images_in_one_reply(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $conversation = ChatConversation::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($administrator, 'admin')->postJson(
            route('admin.chat.messages.store', $conversation),
            ['attachments' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')]],
        );

        $response->assertCreated()->assertJsonCount(2, 'messages');
        $this->assertSame(2, ChatMessage::query()->where('conversation_id', $conversation->id)->where('type', 'image')->count());
    }
}
