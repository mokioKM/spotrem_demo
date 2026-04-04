@extends('admin.layout')

@section('title', '入居者一覧')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">入居者一覧</h1>
    </div>

    <form method="get" action="{{ route('admin.residents.index') }}" class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label for="property_id" class="mb-1 block text-sm font-medium text-slate-700">物件で絞り込み</label>
            <select name="property_id" id="property_id"
                    class="min-w-[12rem] rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                <option value="">すべて</option>
                @foreach ($properties as $p)
                    <option value="{{ $p->id }}" @selected((int) ($filters['property_id'] ?? 0) === (int) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">適用</button>
        @if (!empty($filters['property_id']))
            <a href="{{ route('admin.residents.index') }}" class="text-sm text-slate-600 underline hover:text-slate-900">クリア</a>
        @endif
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-slate-700">物件</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">氏名</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">部屋</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">電話</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">状態</th>
                <th class="px-4 py-3 text-right font-medium text-slate-700"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($residents as $r)
                <tr>
                    <td class="px-4 py-3 text-slate-900">{{ $r->property?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-900">{{ $r->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->room_number }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->phone }}</td>
                    <td class="px-4 py-3">
                        @if ($r->is_active)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">有効</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">無効</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @php
                            $editParams = ['resident' => $r];
                            if (! empty($filters['property_id'])) {
                                $editParams['return_property_id'] = $filters['property_id'];
                            }
                        @endphp
                        <a href="{{ route('admin.residents.edit', $editParams) }}" class="text-slate-700 underline hover:text-slate-900">編集</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">入居者がいません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $residents->links() }}
    </div>
@endsection
