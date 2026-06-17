@extends('admin.layout')

@section('title', 'ログイン')

@section('content')
    <div class="mx-auto w-full max-w-xl rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="p-8 sm:p-10">
            <h1 class="mb-8 text-xl font-semibold text-slate-900">管理画面ログイン</h1>
            <form method="post" action="{{ route('admin.login.post') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">メールアドレス</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="username"
                           class="mt-2 w-full rounded-md border border-slate-300 px-4 py-3 text-base shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">パスワード</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password"
                           class="mt-2 w-full rounded-md border border-slate-300 px-4 py-3 text-base shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                </div>
                <div>
                    <label for="show-password" class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" id="show-password"
                               class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        パスワードを表示
                    </label>
                </div>
                <div class="pt-4">
                    <button type="submit"
                            class="w-full rounded-md bg-slate-900 px-4 py-3 text-base font-medium text-white hover:bg-slate-800">
                        ログイン
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var checkbox = document.getElementById('show-password');
            var password = document.getElementById('password');
            if (!checkbox || !password) return;

            checkbox.addEventListener('change', function () {
                password.type = checkbox.checked ? 'text' : 'password';
            });
        })();
    </script>
@endpush
