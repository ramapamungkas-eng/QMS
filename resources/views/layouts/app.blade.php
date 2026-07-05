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
        $navigation = [
            ['title' => 'Dashboard', 'icon' => 'o-sparkles', 'link' => '/'],

            [
                'title' => 'Inspections',
                'icon' => 'o-clipboard-document-check',
                'children' => [
                    ['title' => 'Stamping', 'link' => route('inspections.stamping.index')],
                    ['title' => 'Station Spot', 'link' => route('inspections.station-spot.index')],
                    ['title' => 'Portable Spot', 'link' => route('inspections.portable-spot.index')],
                    ['title' => 'Robot Spot', 'link' => route('inspections.robot-spot.index')],
                ],
            ],

            [
                'title' => 'Master Data',
                'icon' => 'o-circle-stack',
                'roles' => [\App\Enums\UserRole::Manager, \App\Enums\UserRole::LeaderAdmin],
                'children' => [
                    ['title' => 'Parts', 'link' => route('parts.index')],
                    ['title' => 'Hardware Types', 'link' => route('hardware.index')],
                    ['title' => 'Work Stations', 'link' => route('work-stations.index')],
                ],
            ],

            ['title' => 'Reports', 'icon' => 'o-chart-bar', 'link' => '/reports'], // TODO: route('reports.index')

            [
                'title' => 'Management',
                'icon' => 'o-users',
                'roles' => [\App\Enums\UserRole::Manager, \App\Enums\UserRole::LeaderAdmin],
                'children' => [
                    ['title' => 'Users', 'icon' => 'o-user', 'link' => route('users.index')],
                ],
            ],
        ];
    @endphp

    {{-- MAIN --}}
    <x-main>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <x-app-brand class="px-5 pt-4" />

            {{-- MENU --}}
            <x-menu activate-by-route>

                <x-menu-separator />

                @foreach ($navigation as $item)
                    @continue(isset($item['roles']) && ! in_array(auth()->user()->role, $item['roles'], true))

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

            <div class="mt-auto px-3 pb-4">
                <x-menu-separator />

                @if($user = auth()->user())
                    <x-dropdown class="w-full">
                        <x-slot:trigger>
                            <x-list-item :item="$user" value="name" sub-value="nik" no-separator no-hover class="-mx-2 !-my-2 cursor-pointer rounded hover:bg-base-200">
                                <x-slot:avatar>
                                    <img src="{{ $user->profilePicUrl() }}" class="h-9 w-9 rounded-full object-cover" />
                                </x-slot:avatar>
                                <x-slot:actions>
                                    <x-icon name="o-chevron-up-down" class="h-4 w-4 text-base-content/40" />
                                </x-slot:actions>
                            </x-list-item>
                        </x-slot:trigger>

                        <x-menu-item title="Toggle theme" icon="o-moon" @click="$dispatch('mary-toggle-theme')" />
                        <x-menu-separator />
                        <x-menu-item
                            title="Log out"
                            icon="o-power"
                            link="/logout"
                            no-wire-navigate
                            onclick="return confirm('Are you sure you want to log out?')"
                        />
                    </x-dropdown>
                @endif
            </div>

        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>