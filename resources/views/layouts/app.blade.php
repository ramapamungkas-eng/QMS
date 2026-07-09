<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-[#e8eaee]">

    {{--
        Navigation config.
        - 'roles' (optional): only shown if auth()->user()->role is in this list. Omit to show to everyone.
        - 'children' (optional): renders as a collapsible x-menu-sub instead of a single x-menu-item.
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

        // Top-bar tabs mirror the main sections.
        $topTabs = array_values(array_filter($navigation, fn ($item) => ! isset($item['roles']) || in_array($user->role, $item['roles'], true)));

        // Build a simple breadcrumb from the current route.
        $currentRouteName = Route::currentRouteName() ?? '';
        $currentTitle = match (true) {
            str_starts_with($currentRouteName, 'inspections.') => 'Inspections',
            str_starts_with($currentRouteName, 'reports') => 'Reports',
            str_starts_with($currentRouteName, 'users') => 'Users',
            str_starts_with($currentRouteName, 'parts') => 'Parts',
            str_starts_with($currentRouteName, 'hardware') => 'Hardware Types',
            str_starts_with($currentRouteName, 'work-stations') => 'Work Stations',
            str_starts_with($currentRouteName, 'checklists') => 'Checklists',
            str_starts_with($currentRouteName, 'profile') => 'Profile',
            default => 'Dashboard',
        };
    @endphp

    {{-- TOP HEADER --}}
    <x-nav sticky class="h-11 min-h-[44px] bg-[#1a2b47] text-white shadow-sm lg:px-0">
        <x-slot:brand>
            <div class="flex items-center gap-2 pl-3 lg:pl-4">
                <div class="grid h-7 w-7 place-items-center rounded bg-[#3b6fd6] text-sm">
                    <x-icon name="o-cog" class="h-4 w-4 text-white" />
                </div>
                <div class="leading-tight">
                    <div class="text-[15px] font-bold">QJUN</div>
                    <div class="text-[9px] text-[#b9c4da]">Quality Management System</div>
                </div>
            </div>
        </x-slot:brand>

        {{-- Horizontal tabs -- hidden on mobile --}}
        <x-slot:menu>
            <div class="hidden h-full items-stretch lg:flex">
                @foreach ($topTabs as $tab)
                    @php
                        $tabUrl = $tab['link'] ?? ($tab['children'][0]['link'] ?? '#');
                        $isActive = request()->is(trim(parse_url($tabUrl, PHP_URL_PATH) ?? $tabUrl, '/')) || (isset($tab['children']) && collect($tab['children'])->contains(fn ($c) => request()->is(trim(parse_url($c['link'], PHP_URL_PATH) ?? $c['link'], '/'))));
                    @endphp
                    <a
                        href="{{ $tabUrl }}"
                        @class([
                            'flex items-center border-r border-[#2b3f60] px-5 text-[13px] transition',
                            'bg-white font-bold text-[#1a2b47]' => $isActive,
                            'text-[#b9c4da] hover:bg-[#2b3f60] hover:text-white' => ! $isActive,
                        ])
                    >
                        {{ $tab['title'] }}
                    </a>
                @endforeach
            </div>
        </x-slot:menu>

        <x-slot:actions>
            <x-button
                icon="o-question-mark-circle"
                label="Manual"
                class="mr-2 hidden bg-[#2b3f60] text-white hover:bg-[#3a4f70] sm:flex"
                responsive
            />

            {{-- Mobile hamburger --}}
            <label for="main-drawer" class="me-2 cursor-pointer p-2 lg:hidden">
                <x-icon name="o-bars-3" class="h-6 w-6" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- BREADCRUMB BAR --}}
    <div class="flex items-center justify-between border-b border-[#d7dbe2] bg-[#f4f5f7] px-3 py-1.5 sm:px-4">
        <div class="flex items-center gap-2">
            <x-button
                icon="o-chevron-left"
                link="javascript:history.back()"
                class="btn-ghost btn-xs h-7 w-7 p-0 text-[#888]"
            />
            <x-breadcrumbs
                :items="[['label' => $currentTitle]]"
                class="bg-transparent p-0 text-[13px]"
                link-item-class="font-bold text-[#333]"
                separator="o-chevron-right"
                separator-class="text-[#888] text-xs"
            />
        </div>

        <div class="hidden items-center gap-4 text-xs sm:flex">
            <a href="#" class="flex items-center gap-1 font-bold text-[#e05a3c]">
                <x-icon name="o-exclamation-triangle" class="h-3.5 w-3.5" />
                IT Service Desk
            </a>
            <a href="#" class="flex items-center gap-1 font-bold text-[#e05a3c]">
                <x-icon name="o-exclamation-triangle" class="h-3.5 w-3.5" />
                FAQ & Guide
            </a>
        </div>
    </div>

    {{-- MAIN --}}
    <x-main>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-[#16233d] text-[#cdd6e5]">

            {{-- Section header --}}
            <div class="flex items-center justify-between bg-[#ff8a30] px-3 py-2 text-sm font-bold text-white">
                <span>Quality Activity</span>
                <x-icon name="o-arrow-path" class="h-3.5 w-3.5" />
            </div>

            {{-- MENU --}}
            <x-menu
                activate-by-route
                active-bg-color="bg-[#2b5cad]"
                class="text-[12.5px] [&_.mary-active-menu]:!text-white [&_.mary-active-menu]:!font-bold"
            >
                @foreach ($navigation as $item)
                    @continue(isset($item['roles']) && ! in_array(auth()->user()->role, $item['roles'], true))

                    @if (! empty($item['separator']))
                        <x-menu-separator class="!border-[#1f2f4e]" />
                    @endif

                    @if (isset($item['children']))
                        <x-menu-sub :title="$item['title']" :icon="$item['icon'] ?? null">
                            @foreach ($item['children'] as $child)
                                <x-menu-item
                                    :title="$child['title']"
                                    :icon="$child['icon'] ?? null"
                                    :link="$child['link']"
                                    class="pl-7 text-[#a9b4cc] hover:bg-[#1f2f4e]"
                                />
                            @endforeach
                        </x-menu-sub>
                    @else
                        <x-menu-item
                            :title="$item['title']"
                            :icon="$item['icon'] ?? null"
                            :link="$item['link']"
                            class="hover:bg-[#1f2f4e]"
                        />
                    @endif
                @endforeach
            </x-menu>

            {{-- User profile footer --}}
            <div class="mt-auto border-t border-[#1f2f4e] px-3 py-3">
                @if($user = auth()->user())
                    <x-dropdown class="w-full" position="top" no-x-anchor>
                        <x-slot:trigger>
                            <button class="flex w-full items-center gap-3 rounded px-2 py-2 text-left text-sm transition hover:bg-[#1f2f4e]">
                                <img src="{{ $user->profilePicUrl() }}" class="h-8 w-8 rounded-full object-cover ring-2 ring-white/20" />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium text-white/90">{{ $user->name }}</div>
                                    <div class="truncate text-xs text-[#9aa7c2]">{{ $user->nik }}</div>
                                </div>
                                <x-icon name="o-chevron-up-down" class="h-3.5 w-3.5 shrink-0 text-[#7c8bab]" />
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

            <p class="hidden-when-collapsed border-t border-[#1f2f4e] px-3 py-3 text-[11px] leading-relaxed text-[#9aa7c2]">
                "QJUN is derived from the Japanese word 基準 (Kijun), meaning Standard."
            </p>
        </x-slot:sidebar>

        {{-- CONTENT --}}
        <x-slot:content>
            <div class="max-w-full p-2 sm:p-3 lg:p-4">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>

    {{-- TOAST --}}
    <x-toast />
</body>
</html>
