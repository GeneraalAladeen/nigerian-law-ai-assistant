<?php

namespace App\Livewire;

use App\Agents\NigerianLegalAgent;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Streaming\Events\TextDelta;
use Livewire\Component;

class ChatInterface extends Component
{
    public string $message = '';

    public array $messages = [];

    public ?string $conversationId = null;

    public bool $loading = false;

    public array $conversations = [];

    public function mount(?string $conversationId = null): void
    {
        $this->loadConversations();

        if ($conversationId) {
            $this->loadConversation($conversationId);
        }
    }

    public function loadConversations(): void
    {
        $this->conversations = DB::table('agent_conversations')
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'updated_at' => $c->updated_at,
            ])
            ->toArray();
    }

    public function loadConversation(string $conversationId): void
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $conversation) {
            return;
        }

        $this->conversationId = $conversationId;

        $this->messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();
    }

    public function newConversation(): void
    {
        $this->conversationId = null;
        $this->messages = [];
        $this->message = '';
    }

    public function sendMessage(): void
    {
        $userMessage = trim($this->message);

        if (empty($userMessage)) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $userMessage];
        $this->message = '';
        $this->loading = true;

        $user = auth()->user();
        $agent = new NigerianLegalAgent;

        if ($this->conversationId) {
            $response = $agent->continue($this->conversationId, $user)->stream($userMessage);
        } else {
            $response = $agent->forUser($user)->stream($userMessage);
        }

        $fullText = '';

        foreach ($response as $event) {
            if ($event instanceof TextDelta) {
                $fullText .= $event->delta;
                $this->stream($event->delta)->to('streamed-response');
            }
        }

        if (! $this->conversationId) {
            $this->conversationId = $agent->currentConversation();
        }

        $this->messages[] = ['role' => 'assistant', 'content' => $fullText];
        $this->loading = false;

        $this->loadConversations();
    }

    public function render()
    {
        return view('livewire.chat-interface');
    }
}
