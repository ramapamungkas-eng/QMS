<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    <div class="flex flex-col items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-md text-center">
            <div class="grid h-20 w-20 place-items-center rounded-full bg-error/10 mx-auto mb-6">
                <x-icon name="o-exclamation-triangle" class="h-10 w-10 text-error" />
            </div>
            <h1 class="text-5xl font-black text-error mb-2">403</h1>
            <p class="text-lg text-base-content/60 mb-2">You don't have permission to access this page.</p>
            @php
                $user = auth()->user();
            @endphp
            @if ($user)
                <p class="text-sm text-base-content/40 mb-6">
                    Logged in as <strong>{{ $user->name }}</strong> ({{ $user->role->value }})
                    &middot; Process: <code class="bg-base-300 px-1.5 py-0.5 rounded font-mono">{{ $user->process?->name ?? 'none' }}</code>
                </p>
            @endif
            <a href="/" class="btn btn-primary">
                <x-icon name="o-arrow-left" class="h-4 w-4" />
                Return to dashboard
            </a>
        </div>
    </div>
</body>
</html>
