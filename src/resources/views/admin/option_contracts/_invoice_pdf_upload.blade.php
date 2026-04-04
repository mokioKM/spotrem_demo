{{-- 変数: $title, $hint (任意), $fieldId, $name (既定 invoice_pdf), $required (bool) --}}
@php
    $name = $name ?? 'invoice_pdf';
    $fieldId = $fieldId ?? 'invoice_pdf';
    $required = $required ?? false;
@endphp
<div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 shadow-sm">
    <div class="space-y-3">
        <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch sm:gap-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="file" name="{{ $name }}" id="{{ $fieldId }}" accept="application/pdf,.pdf"
                       class="sr-only invoice-pdf-input"
                       @if ($required) required @endif />
                <label for="{{ $fieldId }}"
                       class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-slate-900 px-5 py-3 text-center text-sm font-semibold text-white shadow-md transition hover:bg-slate-800 active:scale-[0.99] sm:w-auto sm:min-w-[11rem]">
                    PDF を選択
                </label>
            </div>
            <div class="flex min-h-[3rem] flex-1 items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600"
                 data-for="{{ $fieldId }}">
                <span class="invoice-pdf-chosen text-slate-500">未選択</span>
            </div>
        </div>
        @if (! empty($hint))
            <p class="text-xs leading-relaxed text-slate-500">{{ $hint }}</p>
        @endif
        @error($name)
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.body.addEventListener('change', function (e) {
                var t = e.target;
                if (!t || !t.classList || !t.classList.contains('invoice-pdf-input')) return;
                var box = document.querySelector('[data-for="' + t.id + '"]');
                if (!box) return;
                var span = box.querySelector('.invoice-pdf-chosen');
                if (!span) return;
                var f = t.files && t.files[0];
                span.textContent = f ? '選択中: ' + f.name : '未選択';
                span.className = 'invoice-pdf-chosen ' + (f ? 'font-medium text-slate-800' : 'text-slate-500');
            });
        </script>
    @endpush
@endonce
