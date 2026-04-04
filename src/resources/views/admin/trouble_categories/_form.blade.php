@php
    /** @var \App\Models\TroubleCategory|null $category */
@endphp
<div class="space-y-4">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">内部名（英小文字・数字・アンダースコア）</label>
        <input type="text" name="name" id="name" required maxlength="100" pattern="[a-z0-9_]+"
               value="{{ old('name', $category?->name) }}"
               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
               @if($category) readonly @endif />
        @if($category)
            <p class="mt-1 text-xs text-slate-500">内部名は変更できません（新規作成時のみ設定）。</p>
        @endif
    </div>
    <div>
        <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">表示名</label>
        <input type="text" name="display_name" id="display_name" required maxlength="100"
               value="{{ old('display_name', $category?->display_name) }}"
               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500" />
    </div>
    <div>
        <label for="sort_order" class="mb-1 block text-sm font-medium text-slate-700">表示順（小さいほど上）</label>
        <input type="number" name="sort_order" id="sort_order" required min="0" max="9999"
               value="{{ old('sort_order', $category?->sort_order ?? 10) }}"
               class="mt-1 w-32 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500" />
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="show_phone_number" value="0" />
        <input type="checkbox" name="show_phone_number" id="show_phone_number" value="1"
               @checked(old('show_phone_number', $category?->show_phone_number ?? false)) />
        <label for="show_phone_number" class="text-sm text-slate-700">電話連絡用種別（LIFF のフォーム種別から除外し、別途電話案内）</label>
    </div>
    <div>
        <label for="emergency_phone" class="mb-1 block text-sm font-medium text-slate-700">緊急連絡電話（任意・ハイフン可）</label>
        <input type="text" name="emergency_phone" id="emergency_phone" maxlength="20"
               value="{{ old('emergency_phone', $category?->emergency_phone) }}"
               class="mt-1 w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500" />
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0" />
        <input type="checkbox" name="is_active" id="is_active" value="1"
               @checked(old('is_active', $category?->is_active ?? true)) />
        <label for="is_active" class="text-sm text-slate-700">有効</label>
    </div>
</div>
