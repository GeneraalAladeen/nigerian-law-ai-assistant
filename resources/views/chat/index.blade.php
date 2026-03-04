<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nigerian Legal Assistant</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-950 min-h-screen flex flex-col">
    <header class="bg-gray-900 border-b border-gray-800 text-white px-6 py-4 shadow flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-emerald-400">Nigerian Legal Assistant</h1>
            <p class="text-gray-400 text-sm">Ask questions about Nigerian law</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-400 text-sm">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-gray-200 text-sm underline transition">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        <livewire:chat-interface :conversationId="$conversationId ?? null" />
    </main>

    @livewireScripts
</body>
</html>
