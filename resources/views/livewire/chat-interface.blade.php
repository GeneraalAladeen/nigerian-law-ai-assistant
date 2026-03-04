<div class="flex flex-1 overflow-hidden">
    {{-- Sidebar --}}
    <aside class="w-72 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <button
                wire:click="newConversation"
                class="w-full bg-green-700 hover:bg-green-800 text-white rounded-lg px-4 py-2 text-sm font-medium transition"
            >
                + New Conversation
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            @foreach($conversations as $conv)
                <button
                    wire:click="loadConversation('{{ $conv['id'] }}')"
                    class="w-full text-left px-4 py-3 text-sm border-b border-gray-100 hover:bg-gray-50 transition
                        {{ $conversationId === $conv['id'] ? 'bg-green-50 border-l-4 border-l-green-700' : '' }}"
                >
                    <div class="font-medium text-gray-800 truncate">{{ $conv['title'] }}</div>
                    <div class="text-gray-400 text-xs mt-1">
                        {{ \Carbon\Carbon::parse($conv['updated_at'])->diffForHumans() }}
                    </div>
                </button>
            @endforeach

            @if(empty($conversations))
                <div class="p-4 text-gray-400 text-sm text-center">
                    No conversations yet
                </div>
            @endif
        </div>
    </aside>

    {{-- Chat Area --}}
    <div class="flex-1 flex flex-col max-w-4xl mx-auto w-full px-4 py-6">
        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto space-y-4 pb-4" id="messages">
            @forelse($messages as $msg)
                <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-2xl px-4 py-3 text-sm
                        {{ $msg['role'] === 'user'
                            ? 'bg-green-700 text-white rounded-br-none'
                            : 'bg-white text-gray-800 shadow rounded-bl-none' }}">
                        {!! nl2br(e($msg['content'])) !!}
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 mt-16">
                    <p class="text-lg">Ask a question about Nigerian law</p>
                    <p class="text-sm mt-1">e.g. "What is the penalty for theft under the Criminal Code?"</p>
                </div>
            @endforelse

            {{-- Streaming response bubble (always in DOM, shown during request) --}}
            <div class="flex justify-start" wire:loading.flex wire:target="sendMessage" style="display: none;">
                <div class="max-w-[75%] rounded-2xl px-4 py-3 text-sm bg-white text-gray-800 shadow rounded-bl-none min-w-12">
                    <span wire:stream="streamed-response" class="whitespace-pre-wrap"></span>
                    <span id="typing-dots" class="inline-flex gap-1 align-middle">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.1s]"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <form wire:submit="sendMessage" class="flex gap-2">
            <input
                wire:model="message"
                type="text"
                placeholder="Ask about Nigerian law..."
                class="flex-1 rounded-full border border-gray-300 px-5 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-600"
                wire:loading.attr="disabled" wire:target="sendMessage"
            >
            <button
                type="submit"
                class="bg-green-700 hover:bg-green-800 text-white rounded-full px-6 py-3 text-sm font-medium transition disabled:opacity-50"
                wire:loading.attr="disabled" wire:target="sendMessage"
            >
                Send
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Hide typing dots once streamed text arrives
        const streamEl = document.querySelector('[wire\\:stream="streamed-response"]');
        if (streamEl) {
            new MutationObserver(() => {
                const dots = document.getElementById('typing-dots');
                if (dots && streamEl.textContent.length > 0) {
                    dots.style.display = 'none';
                }
            }).observe(streamEl, { childList: true, characterData: true, subtree: true });
        }

        // Auto-scroll during streaming and after Livewire updates
        const scrollToBottom = () => {
            const messages = document.getElementById('messages');
            if (messages) messages.scrollTop = messages.scrollHeight;
        };

        // Scroll on Livewire DOM updates
        document.addEventListener('livewire:updated', scrollToBottom);

        // Scroll during streaming via MutationObserver on the messages container
        const messagesEl = document.getElementById('messages');
        if (messagesEl) {
            new MutationObserver(scrollToBottom)
                .observe(messagesEl, { childList: true, characterData: true, subtree: true });
        }
    });

    // Reset typing dots visibility after Livewire re-render
    document.addEventListener('livewire:updated', () => {
        const dots = document.getElementById('typing-dots');
        if (dots) dots.style.display = '';
    });
</script>
