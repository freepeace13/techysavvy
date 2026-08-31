<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite('resources/css/app.css')
    @stack('styles')
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">
    <header class="border-b border-brand-100 bg-surface px-6 py-4">
        <x-brand::logo />
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
        {{ $slot }}
    </main>
</body>
</html>
