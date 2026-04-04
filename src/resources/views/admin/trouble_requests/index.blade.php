@extends('admin.layout')

@section('title', 'トラブル依頼一覧')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">トラブル依頼</h1>
            <p class="mt-1 text-sm text-slate-600">ステータス・物件で絞り込みできます。</p>
        </div>
    </div>

    <form method="get" action="{{ route('admin.trouble-requests.index') }}" class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-white p-4">
        <div>
            <label for="status" class="block text-xs font-medium text-slate-600">ステータス</label>
            <select name="status" id="status" class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">すべて</option>
                @foreach (['pending' => '受付済', 'scheduled' => '日程確定', 'completed' => '完了', 'cancelled' => 'キャンセル'] as $v => $label)
                    <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="property_id" class="block text-xs font-medium text-slate-600">物件</label>
            <select name="property_id" id="property_id" class="mt-1 min-w-[12rem] rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">すべて</option>
                @foreach ($properties as $p)
                    <option value="{{ $p->id }}" @selected((string) ($filters['property_id'] ?? '') === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">絞り込み</button>
        <a href="{{ route('admin.trouble-requests.index') }}" class="text-sm text-slate-600 hover:text-slate-900">クリア</a>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">日時</th>
                    <th class="px-4 py-3">物件 / 部屋</th>
                    <th class="px-4 py-3">種別</th>
                    <th class="px-4 py-3">ステータス</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $r->id }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $r->created_at?->timezone('Asia/Tokyo')->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            {{ $r->property?->name ?? '—' }}
                            @if ($r->resident?->room_number)
                                <span class="text-slate-600">{{ $r->resident->room_number }}号室</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $r->category?->display_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">{{ $r->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.trouble-requests.edit', $r) }}" class="text-slate-700 underline hover:text-slate-900">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">依頼がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
@endsection
