@extends('admin.layout')

@section('title', '退去者詳細')

@section('content')
    @php
        $indexQuery = $returnPropertyId ? ['property_id' => $returnPropertyId] : [];
    @endphp
    <div class="mb-6">
        <a href="{{ route('admin.former-residents.index', $indexQuery) }}" class="text-sm text-slate-600 hover:text-slate-900">← 退去者一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">退去者詳細</h1>
        <p class="mt-1 text-sm text-slate-600">
            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">{{ $resident->occupancyStatusLabel() }}</span>
        </p>
    </div>

    <div class="max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">物件</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ $resident->property?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">部屋番号</dt>
                <dd class="mt-1 text-slate-900">{{ $resident->room_number }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">氏名</dt>
                <dd class="mt-1 text-slate-900">{{ $resident->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">年齢</dt>
                <dd class="mt-1 text-slate-900">{{ $resident->age !== null ? $resident->age.'歳' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">電話番号</dt>
                <dd class="mt-1 text-slate-900">{{ $resident->phone }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">登録日時</dt>
                <dd class="mt-1 text-slate-900">{{ $resident->registered_at?->timezone('Asia/Tokyo')->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">最終更新</dt>
                <dd class="mt-1 text-slate-900">{{ $resident->updated_at?->timezone('Asia/Tokyo')->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-slate-500">LINE UID</dt>
                <dd class="mt-1 font-mono text-xs text-slate-800">{{ $resident->line_uid ?: '—' }}</dd>
            </div>
        </dl>
    </div>
@endsection
