@extends('admin.layout')

@section('title', '物件一覧')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">物件一覧</h1>
        <div class="flex flex-wrap items-center gap-3">
            <form method="get" action="{{ route('admin.properties.index') }}" class="flex items-center gap-2 text-sm">
                <label class="flex items-center gap-2 text-slate-700">
                    <input type="hidden" name="include_inactive" value="0">
                    <input type="checkbox" name="include_inactive" value="1" @checked($includeInactive) onchange="this.form.submit()">
                    無効も表示
                </label>
            </form>
            <a href="{{ route('admin.properties.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">新規登録</a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-slate-700">物件名</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">住所</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">地域</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">総室数</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">状態</th>
                <th class="px-4 py-3 text-right font-medium text-slate-700"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($properties as $property)
                <tr>
                    <td class="px-4 py-3 text-slate-900">{{ $property->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit($property->address, 40) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $property->region }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $property->room_count ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($property->is_active)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">有効</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">無効</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.properties.edit', $property) }}" class="text-slate-700 underline hover:text-slate-900">編集</a>
                        <form method="post" action="{{ route('admin.properties.destroy', $property) }}" class="inline" onsubmit="return confirm('この物件を削除しますか？（論理削除・一覧からは消えます）');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-red-700 underline hover:text-red-900">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">物件がありません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $properties->links() }}
    </div>
@endsection
