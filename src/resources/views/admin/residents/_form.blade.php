@php
    /** @var \App\Models\Resident $resident */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Property> $propertyList */
@endphp

<div>
    <label for="property_id" class="mb-1 block text-sm font-medium text-slate-700">物件</label>
    <select name="property_id" id="property_id" required
            class="mt-1 w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
        @foreach ($propertyList as $p)
            <option value="{{ $p->id }}" @selected((int) old('property_id', $resident->property_id) === (int) $p->id)>{{ $p->name }}</option>
        @endforeach
    </select>
    @error('property_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">氏名</label>
    <input type="text" name="name" id="name" required maxlength="100"
           value="{{ old('name', $resident->name) }}"
           class="mt-1 w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
    @error('name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="age" class="mb-1 block text-sm font-medium text-slate-700">年齢（任意）</label>
    <input type="number" name="age" id="age" min="0" max="120"
           value="{{ old('age', $resident->age) }}"
           class="mt-1 w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
    @error('age')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="room_number" class="mb-1 block text-sm font-medium text-slate-700">部屋番号</label>
    <input type="text" name="room_number" id="room_number" required maxlength="20"
           value="{{ old('room_number', $resident->room_number) }}"
           class="mt-1 w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
    @error('room_number')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">電話番号（数字・ハイフン）</label>
    <input type="text" name="phone" id="phone" required maxlength="20"
           value="{{ old('phone', $resident->phone) }}"
           class="mt-1 w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
    @error('phone')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $resident->is_active))>
        有効
    </label>
    @unless(auth('admin')->user()?->isSuperAdmin())
        <p class="mt-1 text-xs text-slate-500">有効／無効の切り替えはスーパー管理者のみ操作できます。</p>
    @endunless
    @error('is_active')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
    <span class="font-medium text-slate-700">LINE UID:</span>
    <span class="font-mono text-xs">{{ $resident->line_uid ?: '—' }}</span>
    <p class="mt-1 text-xs text-slate-500">LINE 連携の識別子です。管理画面からは変更できません。</p>
</div>
