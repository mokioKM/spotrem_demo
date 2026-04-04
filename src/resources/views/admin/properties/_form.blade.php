@php
    /** @var \App\Models\Property|null $property */
    $p = $property;
@endphp

<div>
    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">物件名</label>
    <input type="text" name="name" id="name" required maxlength="200"
           value="{{ old('name', $p?->name) }}"
           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
</div>

<div>
    <label for="address" class="mb-1 block text-sm font-medium text-slate-700">住所</label>
    <textarea name="address" id="address" rows="3" required maxlength="500"
              class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">{{ old('address', $p?->address) }}</textarea>
</div>

<div>
    <label for="region" class="mb-1 block text-sm font-medium text-slate-700">地域（業者マッチング用）</label>
    <input type="text" name="region" id="region" required maxlength="100"
           value="{{ old('region', $p?->region) }}"
           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
</div>

<div>
    <label for="room_count" class="mb-1 block text-sm font-medium text-slate-700">総室数（任意）</label>
    <input type="number" name="room_count" id="room_count" min="1"
           value="{{ old('room_count', $p?->room_count) }}"
           class="mt-1 w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
</div>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true))>
        有効
    </label>
    @unless(auth('admin')->user()?->isSuperAdmin())
        <p class="mt-1 text-xs text-slate-500">無効化はスーパー管理者のみ操作できます。</p>
    @endunless
</div>
