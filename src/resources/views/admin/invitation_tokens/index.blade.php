@extends('admin.layout')

@section('title', '招待管理')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">招待URL</h1>
        <a href="{{ route('admin.invitation-tokens.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">新規発行</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-slate-700">用途</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">対象</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">発行者</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">発行日時</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">有効期限</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">状態</th>
                <th class="px-4 py-3 text-left font-medium text-slate-700">URL</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($tokens as $t)
                @php
                    $status = '有効';
                    if ($t->is_used) {
                        $status = '使用済み';
                    } elseif ($t->expires_at->isPast()) {
                        $status = '期限切れ';
                    }
                    $url = url('/invite?token='.$t->token);
                @endphp
                <tr>
                    <td class="px-4 py-3">{{ $t->role === 'admin_user' ? '担当者' : '業者' }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        @if($t->role === 'admin_user')
                            {{ $t->targetAdminUser?->name ?? '—' }}
                        @else
                            {{ $t->targetVendor?->name ?? '—' }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $t->issuer?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $t->created_at?->timezone('Asia/Tokyo')->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $t->expires_at->timezone('Asia/Tokyo')->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $status }}</td>
                    <td class="px-4 py-3 max-w-xs truncate" title="{{ $url }}">
                        <span class="font-mono text-xs">{{ \Illuminate\Support\Str::limit($url, 40) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">招待はまだありません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $tokens->links() }}</div>
@endsection
