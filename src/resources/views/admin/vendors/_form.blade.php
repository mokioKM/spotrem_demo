@php
    /** @var \App\Models\Vendor|null $vendor */
    /** @var \Illuminate\Support\Collection<int, \App\Models\TroubleCategory> $categories */
    $v = $vendor;
    $selected = collect(old('category_ids', $v?->vendorGenres->pluck('category_id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
    $regionsDefault = $v?->vendorRegions->pluck('region')->implode("\n") ?? '';
    $regionsText = old('regions_text', $regionsDefault);
@endphp

<div>
    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">業者名</label>
    <input type="text" name="name" id="name" required maxlength="200"
           value="{{ old('name', $v?->name) }}"
           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
</div>

<div>
    <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">電話番号（数字・ハイフン）</label>
    <input type="text" name="phone" id="phone" required maxlength="20"
           value="{{ old('phone', $v?->phone) }}"
           class="mt-1 w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
</div>

<div>
    <label for="line_messaging_group_id" class="mb-1 block text-sm font-medium text-slate-700">トラブル共有用 LINE グループ ID（任意）</label>
    <input type="text" name="line_messaging_group_id" id="line_messaging_group_id" maxlength="255"
           value="{{ old('line_messaging_group_id', $v?->line_messaging_group_id) }}"
           placeholder="例）Cxxxxxxxx…（業者と管理担当が同一のグループトーク）"
           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
    <p class="mt-1 text-xs text-slate-500">1 業者につき 1 グループ。入居者からトラブルが届き担当業者が付いたとき、この ID 宛に依頼内容をプッシュします。空欄のときはグループ通知しません。</p>
    @error('line_messaging_group_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="google_calendar_id" class="mb-1 block text-sm font-medium text-slate-700">Google カレンダー ID（任意）</label>
    <input type="text" name="google_calendar_id" id="google_calendar_id" maxlength="255"
           value="{{ old('google_calendar_id', $v?->google_calendar_id) }}"
           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
</div>

<div>
    <p class="mb-2 text-sm font-medium text-slate-700">対応ジャンル（1件以上）</p>
    <div class="grid gap-2 sm:grid-cols-2">
        @foreach ($categories as $category)
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                       @checked(in_array((int) $category->id, $selected, true))>
                {{ $category->display_name }}
            </label>
        @endforeach
    </div>
    @error('category_ids')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="regions_text" class="mb-1 block text-sm font-medium text-slate-700">対応地域（1行に1地域・1件以上）</label>
    <textarea name="regions_text" id="regions_text" rows="4" required
              class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
              placeholder="例）大阪府&#10;東京都">{{ $regionsText }}</textarea>
    @error('regions_text')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('regions')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $v?->is_active ?? true))>
        有効
    </label>
    @unless(auth('admin')->user()?->isSuperAdmin())
        <p class="mt-1 text-xs text-slate-500">無効化はスーパー管理者のみ操作できます。</p>
    @endunless
</div>

@if ($v)
    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <span class="font-medium text-slate-700">LINE 連携:</span>
        @if ($v->line_uid)
            登録済（変更・クリアは招待URL／スーパー管理者向け機能で行います）
        @else
            未登録
        @endif
    </div>
@endif
