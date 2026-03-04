<div class="flex flex-1 overflow-hidden">
    {{-- Sidebar --}}
    <aside class="w-72 bg-gray-900 border-r border-gray-800 flex flex-col">
        <div class="p-4 border-b border-gray-800">
            <button
                wire:click="newConversation"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition"
            >
                + New Conversation
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            @foreach($conversations as $conv)
                <button
                    wire:click="loadConversation('{{ $conv['id'] }}')"
                    class="w-full text-left px-4 py-3 text-sm border-b border-gray-800 hover:bg-gray-800 transition
                        {{ $conversationId === $conv['id'] ? 'bg-gray-800 border-l-4 border-l-emerald-500' : '' }}"
                >
                    <div class="font-medium text-gray-200 truncate">{{ $conv['title'] }}</div>
                    <div class="text-gray-500 text-xs mt-1">
                        {{ \Carbon\Carbon::parse($conv['updated_at'])->diffForHumans() }}
                    </div>
                </button>
            @endforeach

            @if(empty($conversations))
                <div class="p-4 text-gray-500 text-sm text-center">
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
                            ? 'bg-emerald-600 text-white rounded-br-none'
                            : 'bg-gray-800 text-gray-100 rounded-bl-none' }}">
                        {!! nl2br(e($msg['content'])) !!}
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 mt-16">
                    <p class="text-lg">Ask a question about Nigerian law</p>
                    <p class="text-sm mt-1">e.g. "What is the penalty for theft under the Criminal Code?"</p>
                </div>
            @endforelse

            {{-- Streaming response bubble (always in DOM, shown during request) --}}
            <div class="flex justify-start" wire:loading.flex wire:target="sendMessage" style="display: none;">
                <div class="max-w-[75%] rounded-2xl px-4 py-3 text-sm bg-gray-800 text-gray-100 rounded-bl-none min-w-12">
                    <span wire:stream="streamed-response" id="streamed-response" class="whitespace-pre-wrap"></span>
                    <span id="typing-dots" class="inline-flex gap-1 align-middle">
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce [animation-delay:0.1s]"></span>
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <form wire:submit="sendMessage" id="chat-form" class="flex gap-2">
            <input
                wire:model="message"
                id="chat-input"
                type="text"
                placeholder="Ask about Nigerian law..."
                class="flex-1 rounded-full bg-gray-800 border border-gray-700 px-5 py-3 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                wire:loading.attr="disabled" wire:target="sendMessage"
            >
            <button
                id="send-btn"
                type="submit"
                class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-6 py-3 text-sm font-medium transition disabled:opacity-50"
                wire:loading.attr="disabled" wire:target="sendMessage"
            >
                Send
            </button>
            <button
                id="stop-btn"
                type="button"
                style="display: none;"
                class="bg-red-600 hover:bg-red-700 text-white rounded-full px-6 py-3 text-sm font-medium transition flex items-center gap-2"
            >
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                Stop
            </button>
        </form>
    </div>
</div>

<script>
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form       = document.getElementById('chat-form');
        const input      = document.getElementById('chat-input');
        const messagesEl = document.getElementById('messages');
        const sendBtn    = document.getElementById('send-btn');
        const stopBtn    = document.getElementById('stop-btn');

        // Show Stop, hide Send when a message is in flight
        form.addEventListener('submit', () => {
            const text = input.value.trim();
            if (!text) return;

            // Optimistically render user message
            const bubble = document.createElement('div');
            bubble.className = 'flex justify-end optimistic-msg';
            bubble.innerHTML = `<div class="max-w-[75%] rounded-2xl px-4 py-3 text-sm bg-emerald-600 text-white rounded-br-none">${escapeHtml(text).replace(/\n/g, '<br>')}</div>`;
            const streamingBubble = messagesEl.querySelector('[wire\\:loading\\.flex]');
            if (streamingBubble) {
                messagesEl.insertBefore(bubble, streamingBubble);
            } else {
                messagesEl.appendChild(bubble);
            }

            input.value = '';
            messagesEl.scrollTop = messagesEl.scrollHeight;

            sendBtn.style.display = 'none';
            stopBtn.style.display = '';
            stopBtn.disabled = false;
            stopBtn.innerHTML = '<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/></svg> Stop';
        });

        // Stop button: POST to /chat/stop so the server breaks its loop
        stopBtn.addEventListener('click', async () => {
            stopBtn.disabled = true;
            stopBtn.textContent = 'Stopping…';

            await fetch('{{ route('chat.stop') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });
        });

        // Watch the streamed-response span for the __stopped__ sentinel
        const streamEl = document.getElementById('streamed-response');
        if (streamEl) {
            new MutationObserver(() => {
                const dots = document.getElementById('typing-dots');

                if (streamEl.textContent.includes('__stopped__')) {
                    // Strip the sentinel and replace with a styled indicator
                    streamEl.textContent = streamEl.textContent.replace('__stopped__', '').trimEnd();

                    const tag = document.createElement('span');
                    tag.className = 'block mt-2 text-xs text-red-400 italic';
                    tag.textContent = '— generation stopped';
                    streamEl.parentElement.appendChild(tag);

                    if (dots) dots.style.display = 'none';
                    return;
                }

                if (dots && streamEl.textContent.length > 0) {
                    dots.style.display = 'none';
                }
            }).observe(streamEl, { childList: true, characterData: true, subtree: true });
        }

        // Auto-scroll
        const scrollToBottom = () => {
            if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
        };
        document.addEventListener('livewire:updated', scrollToBottom);
        if (messagesEl) {
            new MutationObserver(scrollToBottom)
                .observe(messagesEl, { childList: true, characterData: true, subtree: true });
        }
    });

    // After Livewire re-renders: restore Send button, remove optimistic bubble, reset dots
    document.addEventListener('livewire:updated', () => {
        document.querySelectorAll('.optimistic-msg').forEach(el => el.remove());

        const sendBtn = document.getElementById('send-btn');
        const stopBtn = document.getElementById('stop-btn');
        if (sendBtn) sendBtn.style.display = '';
        if (stopBtn) stopBtn.style.display = 'none';

        const dots = document.getElementById('typing-dots');
        if (dots) dots.style.display = '';
    });
</script>
