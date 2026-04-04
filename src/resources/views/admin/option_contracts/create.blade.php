@extends('admin.layout')

@section('title', 'オプション契約 新規')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.option-contracts.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← 一覧へ</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">オプション契約の登録</h1>
    </div>

    <form method="post" action="{{ route('admin.option-contracts.store') }}" enctype="multipart/form-data" class="max-w-xl space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="resident_id" class="block text-sm font-medium text-slate-700">入居者</label>
            <select name="resident_id" id="resident_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">選択してください</option>
                @foreach ($residents as $r)
                    <option value="{{ $r->id }}" @selected(old('resident_id') == $r->id)>
                        {{ $r->name }}（{{ $r->property?->name ?? '物件未設定' }} @if($r->room_number){{ $r->room_number }}号室@endif）
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">契約名</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="200"
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
            <label for="amount" class="block text-sm font-medium text-slate-700">金額（円）</label>
            <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0" step="0.01"
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
            <label for="due_date" class="block text-sm font-medium text-slate-700">初回請求の支払期限（日付）</label>
            <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" required
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            <p class="mt-1 text-xs text-slate-500">この日付の属する月が最初の請求期間（YYYY-MM）になります。</p>
        </div>
        @include('admin.option_contracts._invoice_pdf_upload', [
            'title' => '請求書 PDF（任意）',
            'hint' => 'PDF のみ。Cloudinary に保存され、初回請求行に紐づきます。',
            'fieldId' => 'invoice_pdf',
            'name' => 'invoice_pdf',
            'required' => false,
        ])
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300" />
            <label for="is_active" class="text-sm text-slate-700">契約を有効にする</label>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
            <button type="submit" class="rounded-md bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800">
                保存する
            </button>
            <a href="{{ route('admin.option-contracts.index') }}" class="rounded-md border border-slate-300 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">一覧へ戻る</a>
        </div>
    </form>
@endsection
