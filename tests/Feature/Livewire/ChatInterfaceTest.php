<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ChatInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ChatInterfaceTest extends TestCase
{
    use RefreshDatabase;

    // ============================================================
    // Render
    // ============================================================

    /**
     * Component renders successfully for authenticated users.
     */
    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->assertSee('Ask a question about Nigerian law');
    }

    // ============================================================
    // newConversation
    // ============================================================

    /**
     * New conversation resets the conversation ID, messages, and input field.
     */
    public function test_new_conversation_resets_all_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->set('conversationId', 'some-id')
            ->set('messages', [['role' => 'user', 'content' => 'hello']])
            ->set('message', 'typing...')
            ->call('newConversation')
            ->assertSet('conversationId', null)
            ->assertSet('messages', [])
            ->assertSet('message', '');
    }

    // ============================================================
    // sendMessage — empty message guard
    // ============================================================

    /**
     * Sending an empty message does not modify the messages list.
     */
    public function test_send_message_ignores_empty_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->set('message', '')
            ->call('sendMessage')
            ->assertSet('messages', []);
    }

    /**
     * Sending a whitespace-only message does not modify the messages list.
     */
    public function test_send_message_ignores_whitespace_only_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->set('message', '   ')
            ->call('sendMessage')
            ->assertSet('messages', []);
    }

    // ============================================================
    // loadConversations
    // ============================================================

    /**
     * Load conversations returns only conversations owned by the authenticated user.
     */
    public function test_load_conversations_excludes_other_users_conversations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        DB::table('agent_conversations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => 'My Conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('agent_conversations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $otherUser->id,
            'title' => 'Other Conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = Livewire::test(ChatInterface::class);

        $this->assertCount(1, $component->conversations);
        $this->assertEquals('My Conversation', $component->conversations[0]['title']);
    }

    /**
     * Conversations are returned with the most recently updated first.
     */
    public function test_load_conversations_orders_by_most_recent_first(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        DB::table('agent_conversations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => 'Older',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        DB::table('agent_conversations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => 'Newer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = Livewire::test(ChatInterface::class);

        $this->assertEquals('Newer', $component->conversations[0]['title']);
        $this->assertEquals('Older', $component->conversations[1]['title']);
    }

    /**
     * Conversations are capped at 50 results.
     */
    public function test_load_conversations_is_limited_to_fifty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $rows = [];
        for ($i = 0; $i < 60; $i++) {
            $rows[] = [
                'id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'title' => "Conversation {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('agent_conversations')->insert($rows);

        $component = Livewire::test(ChatInterface::class);

        $this->assertCount(50, $component->conversations);
    }

    // ============================================================
    // loadConversation
    // ============================================================

    /**
     * Loading a conversation belonging to another user makes no state changes.
     */
    public function test_load_conversation_does_nothing_for_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $convId = Str::uuid()->toString();
        DB::table('agent_conversations')->insert([
            'id' => $convId,
            'user_id' => $otherUser->id,
            'title' => 'Not Mine',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(ChatInterface::class)
            ->call('loadConversation', $convId)
            ->assertSet('conversationId', null)
            ->assertSet('messages', []);
    }

    /**
     * Loading a non-existent conversation makes no state changes.
     */
    public function test_load_conversation_does_nothing_for_nonexistent_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->call('loadConversation', Str::uuid()->toString())
            ->assertSet('conversationId', null)
            ->assertSet('messages', []);
    }

    /**
     * Loading a valid conversation sets the conversation ID and loads its messages in order.
     */
    public function test_load_conversation_sets_id_and_loads_messages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $convId = Str::uuid()->toString();
        DB::table('agent_conversations')->insert([
            'id' => $convId,
            'user_id' => $user->id,
            'title' => 'Legal Question',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $baseMessage = [
            'conversation_id' => $convId,
            'user_id' => $user->id,
            'agent' => 'NigerianLegalAgent',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
        ];

        DB::table('agent_conversation_messages')->insert([
            array_merge($baseMessage, [
                'id' => Str::uuid()->toString(),
                'role' => 'user',
                'content' => 'What is Section 33?',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ]),
            array_merge($baseMessage, [
                'id' => Str::uuid()->toString(),
                'role' => 'assistant',
                'content' => 'Section 33 guarantees the right to life.',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ]);

        $component = Livewire::test(ChatInterface::class)
            ->call('loadConversation', $convId)
            ->assertSet('conversationId', $convId);

        $this->assertCount(2, $component->messages);
        $this->assertEquals('user', $component->messages[0]['role']);
        $this->assertEquals('What is Section 33?', $component->messages[0]['content']);
        $this->assertEquals('assistant', $component->messages[1]['role']);
    }

    // ============================================================
    // mount
    // ============================================================

    /**
     * Mounting with a conversation ID belonging to another user does not load it.
     */
    public function test_mount_ignores_another_users_conversation_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $convId = Str::uuid()->toString();
        DB::table('agent_conversations')->insert([
            'id' => $convId,
            'user_id' => $otherUser->id,
            'title' => 'Not Yours',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Livewire v4 binds mount parameters to matching public properties automatically,
        // so conversationId will be set — but the important security check is that
        // no messages from the other user's conversation are loaded.
        Livewire::test(ChatInterface::class, ['conversationId' => $convId])
            ->assertSet('messages', []);
    }

    /**
     * Mounting with a valid conversation ID loads the conversation immediately.
     */
    public function test_mount_loads_conversation_when_valid_id_provided(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $convId = Str::uuid()->toString();
        DB::table('agent_conversations')->insert([
            'id' => $convId,
            'user_id' => $user->id,
            'title' => 'My Session',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(ChatInterface::class, ['conversationId' => $convId])
            ->assertSet('conversationId', $convId);
    }
}
