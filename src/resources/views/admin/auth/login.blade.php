@extends('admin.layout')

@section('title', 'ログイン')

@section('content')
    <div class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="mb-6 text-xl font-semibold text-slate-900">管理画面ログイン</h1>
        <form method="post" action="{{ route('admin.login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">メールアドレス</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="username"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">パスワード</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
            </div>
            <button type="submit"
                    class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                ログイン
            </button>
        </form>
    </div>
@endsection
