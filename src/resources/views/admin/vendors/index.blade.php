@extends('admin.layout')

@section('title', '業者一覧')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">業者一覧</h1>
        <div class="flex flex-wrap items-center gap-3">
            <form method="get" action="{{ route('admin.vendors.index') }}" class="flex items-center gap-2 text-sm">
                <label class="flex items-center gap-2 text-slate-700">
                    <input type="hidden" name="include_inactive" value="0">
                    <input type="checkbox" name="include_inactive" value="1" @checked($includeInactive) onchange="this.form.submit()">
                    無効も表示
                </label>
            </form>
            <a href="{{ route('admin.vendors.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">新規登録</a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-slate-700">業者名</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">電話</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">対応ジャンル</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">対応地域</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">LINE</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">状態</th>
                <th class="px-4 py-3 text-right font-medium text-slate-700"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($vendors as $vendor)
                <tr>
                    <td class="px-4 py-3 text-slate-900">{{ $vendor->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $vendor->phone }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $vendor->vendorGenres->pluck('troubleCategory.display_name')->filter()->implode('、') ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $vendor->vendorRegions->pluck('region')->implode('、') ?: '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($vendor->line_uid)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">登録済</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">未登録</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($vendor->is_active)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">有効</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">無効</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.vendors.edit', $vendor) }}" class="text-slate-700 underline hover:text-slate-900">編集</a>
                        <form method="post" action="{{ route('admin.vendors.destroy', $vendor) }}" class="inline" onsubmit="return confirm('この業者を削除しますか？（論理削除・一覧からは消えます）');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-red-700 underline hover:text-red-900">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">業者がありません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $vendors->links() }}
    </div>
@endsection
