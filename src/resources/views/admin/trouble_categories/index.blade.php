@extends('admin.layout')

@section('title', '困りごと種別')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">困りごと種別</h1>
        <a href="{{ route('admin.trouble-categories.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">新規登録</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-slate-700">表示順</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">内部名</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">表示名</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">電話種別</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">緊急電話</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">状態</th>
                <th class="px-4 py-3 text-right font-medium text-slate-700"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($categories as $row)
                <tr>
                    <td class="px-4 py-3 text-slate-600">{{ $row->sort_order }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-slate-900">{{ $row->display_name }}</td>
                    <td class="px-4 py-3">
                        @if ($row->show_phone_number)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">電話</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->emergency_phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($row->is_active)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">有効</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">無効</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.trouble-categories.edit', $row) }}" class="text-slate-700 underline hover:text-slate-900">編集</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">種別がありません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
@endsection
