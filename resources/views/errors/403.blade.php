<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; color: #333; }
        .card { text-align: center; padding: 3rem; max-width: 480px; }
        h1 { font-size: 4rem; margin: 0; font-weight: 800; color: #dc2626; }
        p { font-size: 1.1rem; color: #666; line-height: 1.6; }
        code { background: #e5e7eb; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; }
        a { color: #2563eb; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>403</h1>
        <p>You don't have permission to access this page.</p>
        @php
            $user = auth()->user();
        @endphp
        @if ($user)
            <p style="font-size:0.9rem">
                Logged in as <strong>{{ $user->name }}</strong> ({{ $user->role->value }})<br>
                Process: <code>{{ $user->process?->name ?? 'none' }}</code>
            </p>
        @endif
        <p><a href="/">Return to dashboard</a></p>
    </div>
</body>
</html>