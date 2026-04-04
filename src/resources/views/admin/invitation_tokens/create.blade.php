@extends('admin.layout')

@section('title', '招待URL発行')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.invitation-tokens.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← 一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">招待URL発行</h1>
    </div>

    <form method="post" action="{{ route('admin.invitation-tokens.store') }}" class="max-w-xl space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <p class="mb-2 text-sm font-medium text-slate-700">用途</p>
            <label class="mr-6 inline-flex items-center gap-2 text-sm">
                <input type="radio" name="role" value="admin_user" @checked(old('role') === 'admin_user' || old('role') === null) required> 担当者用
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="role" value="vendor" @checked(old('role') === 'vendor') required> 業者用
            </label>
        </div>
        <div id="field-admin">
            <label for="admin_user_id" class="mb-1 block text-sm font-medium text-slate-700">対象担当者</label>
            <select name="admin_user_id" id="admin_user_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($adminUsers as $u)
                    <option value="{{ $u->id }}" @selected((string) old('admin_user_id') === (string) $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div id="field-vendor">
            <label for="vendor_id" class="mb-1 block text-sm font-medium text-slate-700">対象業者</label>
            <select name="vendor_id" id="vendor_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($vendors as $v)
                    <option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">発行</button>
    </form>
@endsection
