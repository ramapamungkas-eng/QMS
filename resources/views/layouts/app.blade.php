<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{--
        Sidebar navigation config.
        - 'roles' (optional): only shown if auth()->user()->role is in this list. Omit to show to everyone.
        - 'children' (optional): renders as a collapsible x-menu-sub instead of a single x-menu-item.
        To add a new page: add one entry here — no new markup needed below.
    --}}
    @php
        $user = auth()->user();
        $isAdmin = in_array($user->role, [\App\Enums\UserRole::Manager, \App\Enums\UserRole::LeaderAdmin], true);

        $routeExists = fn (string $name) => app('router')->has($name);

        $allInspections = [
            ['title' => 'Stamping', 'link' => 'inspections.stamping.index', 'process' => 'Stamping'],
            ['title' => 'Station Spot', 'link' => 'inspections.station-spot.index', 'process' => 'Welding'],
            ['title' => 'Portable Spot', 'link' => 'inspections.portable-spot.index', 'process' => 'Welding'],
            ['title' => 'Robot Spot', 'link' => 'inspections.robot-spot.index', 'process' => 'Welding'],
        ];

        $inspectionChildren = array_values(array_filter(
            $isAdmin ? $allInspections : array_filter($allInspections, fn ($item) => $item['process'] === $user->process?->name),
            fn ($item) => $routeExists($item['link']),
        ));

        // Resolve route names to URLs after filtering
        $inspectionChildren = array_map(fn ($item) => ['title' => $item['title'], 'link' => route($item['link']), 'process' => $item['process']], $inspectionChildren);

        $navigation = [
            ['title' => 'Dashboard', 'icon' => 'o-sparkles', 'link' => '/'],
        ];

        if ($inspectionChildren !== []) {
            $navigation[] = [
                'title' => 'Inspections',
                'icon' => 'o-clipboard-document-check',
                'children' => $inspectionChildren,
            ];
        }

        array_push($navigation,
            ['title' => 'Reports', 'icon' => 'o-chart-bar', 'link' => route('reports.index')],

            [
                'title' => 'Master Data',
                'icon' => 'o-circle-stack',
                'roles' => [\App\Enums\UserRole::Manager, \App\Enums\UserRole::LeaderAdmin],
                'children' => [
                    ['title' => 'Users', 'icon' => 'o-user', 'link' => route('users.index')],
                    ['title' => 'Parts', 'link' => route('parts.index')],
                    ['title' => 'Hardware Types', 'link' => route('hardware.index')],
                    ['title' => 'Work Stations', 'link' => route('work-stations.index')],
                    ['title' => 'Checklists', 'link' => route('checklists.index')],
                ],
            ],
        );
    @endphp

    {{-- MAIN --}}
    <x-main>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-neutral text-neutral-content">

            {{-- BRAND --}}
            <x-app-brand class="px-5 pt-5" />

            {{-- MENU — accent left border on active items --}}
            <x-menu activate-by-route active-bg-color="bg-white/10" class="[&_.mary-active-menu]:!border-l-2 [&_.mary-active-menu]:!border-accent">

                <x-menu-separator class="!border-white/10" />

                @foreach ($navigation as $item)
                    @continue(isset($item['roles']) && ! in_array(auth()->user()->role, $item['roles'], true))

                    @if (! empty($item['separator']))
                        <x-menu-separator class="!border-white/10" />
                    @endif

                    @if (isset($item['children']))
                        <x-menu-sub :title="$item['title']" :icon="$item['icon'] ?? null">
                            @foreach ($item['children'] as $child)
                                <x-menu-item :title="$child['title']" :icon="$child['icon'] ?? null" :link="$child['link']" />
                            @endforeach
                        </x-menu-sub>
                    @else
                        <x-menu-item :title="$item['title']" :icon="$item['icon'] ?? null" :link="$item['link']" />
                    @endif
                @endforeach
            </x-menu>

            {{-- User profile footer --}}
            <div class="mt-auto border-t border-white/10 px-3 py-3">
                @if($user = auth()->user())
                    <x-dropdown class="w-full" position="top" no-x-anchor>
                        <x-slot:trigger>
                            <button class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left text-sm transition hover:bg-white/10">
                                <img src="{{ $user->profilePicUrl() }}" class="h-8 w-8 rounded-full object-cover ring-2 ring-white/20" />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium text-white/90">{{ $user->name }}</div>
                                    <div class="truncate text-xs text-white/40">{{ $user->nik }}</div>
                                </div>
                                <x-icon name="o-chevron-up-down" class="h-3.5 w-3.5 shrink-0 text-white/30" />
                            </button>
                        </x-slot:trigger>

                        <x-menu-item class="text-black/90" title="Profile" icon="o-user" link="{{ route('profile.index') }}" />
                        <x-menu-separator />
                        <x-menu-item
                            class="text-black/90"
                            title="Log out"
                            icon="o-power"
                            link="/logout"
                            no-wire-navigate
                            onclick="return confirm('Are you sure you want to log out?')"
                        />
                    </x-dropdown>
                @endif
            </div>

            <p class="hidden-when-collapsed text-center px-5 py-3 text-xs">
                <span class="text-xs text-white/40">"QJUN is derived from the Japanese word 基準 (Kijun), meaning Standard. It represents the commitment to quality, consistency, and manufacturing excellence."</span>
            </p>

        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            <div class="max-w-full">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>