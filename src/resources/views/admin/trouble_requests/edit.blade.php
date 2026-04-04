@extends('admin.layout')

@section('title', 'トラブル依頼 #' . $request->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.trouble-requests.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← 一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">依頼 #{{ $request->id }}</h1>
        <p class="mt-1 text-sm text-slate-600">ステータス: <span class="font-medium">{{ $request->status }}</span></p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-medium text-slate-900">内容</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">物件</dt>
                    <dd>{{ $request->property?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">入居者 / 部屋</dt>
                    <dd>{{ $request->resident?->name ?? '—' }} / {{ $request->resident?->room_number ?? '—' }}号室</dd>
                </div>
                <div>
                    <dt class="text-slate-500">種別</dt>
                    <dd>{{ $request->category?->display_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">業者</dt>
                    <dd>{{ $request->vendor?->name ?? '（未指定）' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">希望日</dt>
                    <dd>{{ $request->preferred_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">訪問予定</dt>
                    <dd>{{ $request->scheduled_at?->timezone('Asia/Tokyo')->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">詳細</dt>
                    <dd class="whitespace-pre-wrap text-slate-800">{{ $request->description }}</dd>
                </div>
            </dl>
        </div>

        <div class="space-y-6">
            @if ($request->requestAttachments->isNotEmpty())
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-slate-900">添付（Cloudinary）</h2>
                    <p class="mt-1 text-xs text-slate-500">DB には <code class="rounded bg-slate-100 px-1">cloudinary_public_id</code> と配信用 <code class="rounded bg-slate-100 px-1">url</code>（secure_url）を保存しています。</p>
                    <ul class="mt-4 space-y-4 text-sm">
                        @foreach ($request->requestAttachments as $a)
                            <li class="rounded-md border border-slate-100 bg-slate-50 p-3">
                                <p class="font-mono text-xs text-slate-600 break-all">{{ $a->cloudinary_public_id }}</p>
                                @if ($a->file_type === 'image')
                                    <a href="{{ $a->url }}" target="_blank" rel="noopener noreferrer" class="mt-2 block">
                                        <img src="{{ $a->url }}" alt="" class="max-h-48 max-w-full rounded border border-slate-200 object-contain" loading="lazy" />
                                    </a>
                                @elseif ($a->file_type === 'video')
                                    <video src="{{ $a->url }}" controls class="mt-2 max-h-64 w-full rounded border border-slate-200" preload="metadata"></video>
                                @endif
                                <p class="mt-2">
                                    <a href="{{ $a->url }}" target="_blank" rel="noopener noreferrer" class="text-slate-700 underline">{{ $a->file_type }} — 新しいタブで開く</a>
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($request->status === 'pending')
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-slate-900">日程確定</h2>
                    <form method="post" action="{{ route('admin.trouble-requests.schedule', $request) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="scheduled_at" class="block text-sm font-medium text-slate-700">訪問日時</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" required
                                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">確定する</button>
                    </form>
                </div>
            @endif

            @if ($request->status === 'scheduled')
                <div class="rounded-lg border border-green-200 bg-green-50 p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-green-900">対応完了</h2>
                    <p class="mt-2 text-sm text-green-800">現地対応が終わったら完了にしてください。</p>
                    <form method="post" action="{{ route('admin.trouble-requests.complete', $request) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">対応完了</button>
                    </form>
                </div>
            @endif

            @if (in_array($request->status, ['pending', 'scheduled'], true))
                <div class="rounded-lg border border-red-200 bg-red-50 p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-red-900">キャンセル</h2>
                    <form method="post" action="{{ route('admin.trouble-requests.cancel', $request) }}" class="mt-4" onsubmit="return confirm('キャンセルしますか？');">
                        @csrf
                        <button type="submit" class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-100">依頼をキャンセル</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
