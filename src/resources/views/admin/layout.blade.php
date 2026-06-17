<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理画面') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (min-width: 1024px) {
            .admin-shell {
                display: grid;
                grid-template-columns: 16rem minmax(0, 1fr);
            }

            .admin-main-content {
                padding-top: 3rem !important;
            }

            .admin-mobile-header {
                display: none;
            }

            #admin-sidebar {
                position: relative;
                transform: none;
            }
        }

        @media (max-width: 1023px) {
            #admin-sidebar {
                position: fixed;
            }

            #admin-sidebar:not(.translate-x-0) {
                transform: translateX(-100%);
            }

            .admin-main-content {
                padding-top: 2.5rem !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@auth('admin')
    @php
        $navItems = [
            ['route' => 'admin.properties.index', 'pattern' => 'admin.properties.*', 'label' => '物件'],
            ['route' => 'admin.residents.index', 'pattern' => 'admin.residents.*', 'label' => '入居者'],
            ['route' => 'admin.former-residents.index', 'pattern' => 'admin.former-residents.*', 'label' => '退去者'],
            ['route' => 'admin.vendors.index', 'pattern' => 'admin.vendors.*', 'label' => '業者'],
            ['route' => 'admin.trouble-categories.index', 'pattern' => 'admin.trouble-categories.*', 'label' => '困りごと種別'],
            ['route' => 'admin.invitation-tokens.index', 'pattern' => 'admin.invitation-tokens.*', 'label' => '招待'],
            ['route' => 'admin.trouble-requests.index', 'pattern' => 'admin.trouble-requests.*', 'label' => '依頼'],
            ['route' => 'admin.option-contracts.index', 'pattern' => 'admin.option-contracts.*', 'label' => 'オプション'],
        ];
    @endphp

    <div class="admin-shell min-h-screen lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" aria-hidden="true"></div>

        <aside id="admin-sidebar"
               class="flex min-h-screen w-64 flex-col border-r border-slate-800 bg-slate-900 text-white transition-transform duration-200 ease-in-out max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:z-40 max-lg:-translate-x-full lg:min-h-screen">
            <div class="shrink-0 border-b border-slate-700 px-5 py-8">
                <a href="{{ route('admin.properties.index') }}" class="block text-lg font-semibold tracking-tight text-white hover:text-slate-200">
                    SpotRem 管理
                </a>
            </div>

            <nav class="flex flex-1 flex-col gap-3 overflow-y-auto px-4 pb-6 pt-8" aria-label="管理メニュー">
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['pattern']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex w-full cursor-pointer items-center justify-center rounded-lg border px-4 py-3 text-sm font-medium shadow-sm transition-all duration-150
                              {{ $active
                                  ? 'border-slate-400 bg-slate-700 text-white shadow-md hover:border-slate-300 hover:bg-slate-600 hover:shadow-lg'
                                  : 'border-slate-700 bg-slate-800 text-slate-200 hover:border-slate-400 hover:bg-slate-600 hover:text-white hover:shadow-lg hover:ring-1 hover:ring-slate-500/60' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto shrink-0 border-t border-slate-700 px-5 py-8">
                <p class="truncate text-sm font-medium text-slate-200">{{ Auth::guard('admin')->user()->name }}</p>
                <form method="post" action="{{ route('admin.logout') }}" class="mt-7 pt-2">
                    @csrf
                    <button type="submit"
                            class="w-full cursor-pointer rounded-lg border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-200 shadow-sm transition-all duration-150 hover:border-slate-400 hover:bg-slate-600 hover:text-white hover:shadow-lg hover:ring-1 hover:ring-slate-500/60">
                        ログアウト
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="admin-mobile-header sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
                <button type="button"
                        id="sidebar-toggle"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white p-2 text-slate-700 hover:bg-slate-50"
                        aria-controls="admin-sidebar"
                        aria-expanded="false"
                        aria-label="メニューを開く">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="text-sm font-semibold text-slate-800">SpotRem 管理</span>
            </header>

            <main class="admin-main-content flex-1 px-4 pb-6 pt-10 sm:px-6 lg:px-8 lg:pb-8 lg:pt-12">
                @include('admin._flash')
                @yield('content')
            </main>
        </div>
    </div>
@else
    <main class="relative flex min-h-screen items-center justify-center px-6 py-12 sm:px-8">
        <div class="pointer-events-none absolute inset-x-0 top-6 z-20 px-6 sm:px-8">
            <div class="pointer-events-auto mx-auto w-full max-w-xl">
                @include('admin._flash')
            </div>
        </div>
        @yield('content')
    </main>
@endauth
@stack('scripts')
@auth('admin')
    <script>
        (function () {
            var sidebar = document.getElementById('admin-sidebar');
            var toggle = document.getElementById('sidebar-toggle');
            var backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !toggle || !backdrop) return;

            function isMobile() {
                return window.innerWidth < 1024;
            }

            function setOpen(open) {
                if (isMobile()) {
                    sidebar.classList.toggle('-translate-x-full', !open);
                    sidebar.classList.toggle('translate-x-0', open);
                    backdrop.classList.toggle('hidden', !open);
                } else {
                    sidebar.classList.remove('-translate-x-full', 'translate-x-0');
                    backdrop.classList.add('hidden');
                }

                toggle.setAttribute('aria-expanded', open && isMobile() ? 'true' : 'false');
                toggle.setAttribute('aria-label', open && isMobile() ? 'メニューを閉じる' : 'メニューを開く');
            }

            toggle.addEventListener('click', function () {
                setOpen(!sidebar.classList.contains('translate-x-0'));
            });

            backdrop.addEventListener('click', function () {
                setOpen(false);
            });

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile()) {
                        setOpen(false);
                    }
                });
            });

            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    setOpen(false);
                }
            });
        })();
    </script>
@endauth
</body>
</html>
