<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat — Renty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
    <style>body{background:#f8fafc}</style>
</head>
<body class="min-h-screen">
    <div class="p-6">
        @livewire('chat-panel')
    </div>

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
