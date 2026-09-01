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
    <header class="bg-pegboard border-b border-steel-200 bg-surface-muted">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
            <x-brand::logo />
            <span class="hidden font-mono text-xs uppercase tracking-widest text-ink-muted sm:inline">
                internal tools
            </span>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
        {{ $slot }}
    </main>
</body>
</html>
