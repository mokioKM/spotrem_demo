@extends('admin.layout')

@section('title', 'オプション契約 編集')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.option-contracts.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← 一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">オプション契約の編集</h1>
    </div>

    <form method="post" action="{{ route('admin.option-contracts.update', $contract) }}" enctype="multipart/form-data" class="max-w-xl space-y-6 rounded-lg border border-slate-200 bg-white p-6 pb-8 shadow-sm">
        @csrf
        @method('put')
        <div>
            <label for="resident_id" class="block text-sm font-medium text-slate-700">入居者</label>
            <select name="resident_id" id="resident_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">選択してください</option>
                @foreach ($residents as $r)
                    <option value="{{ $r->id }}" @selected(old('resident_id', $contract->resident_id) == $r->id)>
                        {{ $r->name }}（{{ $r->property?->name ?? '物件未設定' }} @if($r->room_number){{ $r->room_number }}号室@endif）
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">契約名</label>
            <input type="text" name="name" id="name" value="{{ old('name', $contract->name) }}" required maxlength="200"
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
            <label for="amount" class="block text-sm font-medium text-slate-700">金額（円）</label>
            <input type="number" name="amount" id="amount" value="{{ old('amount', $contract->amount) }}" required min="0" step="0.01"
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
            <label for="due_date" class="block text-sm font-medium text-slate-700">初回請求の支払期限（日付）</label>
            <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $contract->due_date?->format('Y-m-d')) }}" required
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            <p class="mt-1 text-xs text-slate-500">編集で変更しても、既存の請求期間行の billing_period は自動では変わりません（要は契約マスタの表示用）。</p>
        </div>

        @include('admin.option_contracts._invoice_pdf_upload', [
            'title' => '請求書 PDF（差し替え・任意）',
            'hint' => 'アップロードすると、最も早い請求期間の行の請求書が Cloudinary 上で差し替わります（PDF のみ）。',
            'fieldId' => 'invoice_pdf',
            'name' => 'invoice_pdf',
            'required' => false,
        ])

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $contract->is_active)) class="rounded border-slate-300" />
            <label for="is_active" class="text-sm text-slate-700">契約を有効にする</label>
        </div>

        <div class="mt-2 rounded-lg border-2 border-slate-200 bg-slate-50/80 p-5 shadow-inner">
            <p class="mb-3 text-sm font-semibold text-slate-800">変更の保存</p>
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-slate-800">
                    保存する
                </button>
                <a href="{{ route('admin.option-contracts.index') }}" class="rounded-lg px-3 py-3 text-sm text-slate-600 underline hover:text-slate-900">一覧へ</a>
            </div>
        </div>
    </form>

    <div class="mt-10">
        <h2 class="text-lg font-semibold text-slate-900">請求一覧（請求書 PDF・入金確認）</h2>
        <p class="mt-1 text-sm text-slate-600">各請求期間ごとに PDF をアップロードし、入金後に管理側で確認できます。保存後は一覧に戻ります。</p>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">請求期間</th>
                    <th class="px-4 py-3">期限</th>
                    <th class="px-4 py-3">ステータス</th>
                    <th class="px-4 py-3">請求書</th>
                    <th class="px-4 py-3">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($contract->optionBillings as $b)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $b->billing_period }}</td>
                        <td class="px-4 py-3">{{ $b->due_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $b->status }}</span>
                            @if ($b->paid_at)
                                <div class="mt-1 text-xs text-slate-500">入金: {{ $b->paid_at->timezone('Asia/Tokyo')->format('Y-m-d H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($b->invoice_pdf_url)
                                <a href="{{ route('admin.option-billings.invoice-pdf', $b) }}" class="inline-flex items-center gap-1 text-slate-700 underline hover:text-slate-900">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3"/></svg>
                                    PDF
                                </a>
                                @if ($b->invoice_pdf_filename)
                                    <div class="text-xs text-slate-500">{{ $b->invoice_pdf_filename }}</div>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="space-y-4">
                                <form method="post" action="{{ route('admin.option-billings.invoice', $b) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    @include('admin.option_contracts._invoice_pdf_upload', [
                                        'title' => '請求書 PDF（Cloudinary に保存）',
                                        'hint' => 'この請求期間行に紐づけて保存されます。',
                                        'fieldId' => 'invoice_pdf_'.$b->id,
                                        'name' => 'invoice_pdf',
                                        'required' => true,
                                    ])
                                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 sm:w-auto">
                                        アップロードして保存
                                    </button>
                                </form>
                                @if ($b->status !== 'paid')
                                    <form method="post" action="{{ route('admin.option-billings.confirm-paid', $b) }}" onsubmit="return confirm('入金を確認しましたか？');">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-green-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-600">入金確認（管理）</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">請求行がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
