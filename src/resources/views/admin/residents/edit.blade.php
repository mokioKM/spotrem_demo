@extends('admin.layout')

@section('title', '入居者編集')

@section('content')
    @php
        $indexQuery = $returnPropertyId ? ['property_id' => $returnPropertyId] : [];
    @endphp
    <div class="mb-6">
        <a href="{{ route('admin.residents.index', $indexQuery) }}" class="text-sm text-slate-600 hover:text-slate-900">← 一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">入居者編集</h1>
    </div>

    <form method="post" action="{{ route('admin.residents.update', $resident) }}" class="max-w-3xl space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('put')
        @if ($returnPropertyId)
            <input type="hidden" name="redirect_property_id" value="{{ $returnPropertyId }}">
        @endif
        @include('admin.residents._form', ['resident' => $resident, 'propertyList' => $propertyList])
        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">更新</button>
            <a href="{{ route('admin.residents.index', $indexQuery) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">キャンセル</a>
        </div>
    </form>
@endsection
