@extends('admin.layout')

@section('title', '通知設定')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">通知設定</h1>
        <p class="mt-1 text-sm text-slate-600">トラブル新規依頼など、管理会社向けLINEグループの送信先IDを設定します。</p>
    </div>

    <form method="post" action="{{ route('admin.settings.notification-group.update') }}" class="max-w-xl space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="notification_group_line_uid" class="block text-sm font-medium text-slate-700">グループ / ルーム ID（LINE）</label>
            <input type="text" name="notification_group_line_uid" id="notification_group_line_uid"
                   value="{{ old('notification_group_line_uid', $notification_group_line_uid) }}"
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono"
                   placeholder="Uxxxxxxxx..." autocomplete="off" />
            <p class="mt-2 text-xs text-slate-500">未設定の場合、グループ通知はスキップされログに記録されます。</p>
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">保存</button>
    </form>
@endsection
