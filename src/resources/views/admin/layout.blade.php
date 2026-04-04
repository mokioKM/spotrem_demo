<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理画面') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ route('admin.properties.index') }}" class="text-lg font-semibold text-slate-800">SpotRem 管理</a>
            <nav class="flex flex-wrap gap-3 text-sm">
                <a href="{{ route('admin.properties.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.properties.*')) font-medium text-slate-900 @endif">物件</a>
                <a href="{{ route('admin.residents.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.residents.*')) font-medium text-slate-900 @endif">入居者</a>
                <a href="{{ route('admin.vendors.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.vendors.*')) font-medium text-slate-900 @endif">業者</a>
                <a href="{{ route('admin.trouble-categories.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.trouble-categories.*')) font-medium text-slate-900 @endif">困りごと種別</a>
                <a href="{{ route('admin.invitation-tokens.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.invitation-tokens.*')) font-medium text-slate-900 @endif">招待</a>
                <a href="{{ route('admin.trouble-requests.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.trouble-requests.*')) font-medium text-slate-900 @endif">依頼</a>
                <a href="{{ route('admin.option-contracts.index') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.option-contracts.*')) font-medium text-slate-900 @endif">オプション</a>
                <a href="{{ route('admin.settings.notification-group') }}" class="text-slate-600 hover:text-slate-900 @if(request()->routeIs('admin.settings.*')) font-medium text-slate-900 @endif">設定</a>
            </nav>
        </div>
        @auth('admin')
            <div class="flex items-center gap-4 text-sm">
                <span class="text-slate-600">{{ Auth::guard('admin')->user()->name }}</span>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-100">ログアウト</button>
                </form>
            </div>
        @endauth
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-8">
    @if (session('status'))
        <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>
@stack('scripts')
</body>
</html>
