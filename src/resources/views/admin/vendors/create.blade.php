@extends('admin.layout')

@section('title', '業者登録')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.vendors.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← 一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">業者登録</h1>
    </div>

    <form method="post" action="{{ route('admin.vendors.store') }}" class="max-w-3xl space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('admin.vendors._form', ['vendor' => null, 'categories' => $categories])
        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">保存</button>
            <a href="{{ route('admin.vendors.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">キャンセル</a>
        </div>
    </form>
@endsection
