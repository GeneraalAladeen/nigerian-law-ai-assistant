<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nigerian Legal Assistant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-green-800 text-white px-6 py-4 shadow flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold">Nigerian Legal Assistant</h1>
            <p class="text-green-200 text-sm">Ask questions about Nigerian law</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-green-200 text-sm">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-green-200 hover:text-white text-sm underline">
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
