@extends('admin.layout')

@section('title', 'オプション契約')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">オプション契約</h1>
            <p class="mt-1 text-sm text-slate-600">付帯サービス等の契約と請求の起点です。</p>
        </div>
        <a href="{{ route('admin.option-contracts.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">新規登録</a>
    </div>

    @error('send')
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $message }}</div>
    @enderror

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">入居者</th>
                    <th class="px-4 py-3">契約名</th>
                    <th class="px-4 py-3">金額</th>
                    <th class="px-4 py-3">締め日</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($contracts as $c)
                    @php
                        $hasPdf = $c->optionBillings->contains(static fn ($b) => is_string($b->invoice_pdf_url) && $b->invoice_pdf_url !== '');
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            {{ $c->resident?->name ?? '—' }}
                            @if ($c->resident?->property)
                                <span class="block text-xs text-slate-500">{{ $c->resident->property->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $c->name }}</td>
                        <td class="px-4 py-3">¥{{ number_format((float) $c->amount) }}</td>
                        <td class="px-4 py-3">{{ $c->due_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            @if ($c->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">有効</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">無効</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <a href="{{ route('admin.option-contracts.edit', $c) }}" class="text-slate-700 underline hover:text-slate-900">編集</a>
                                <form method="post" action="{{ route('admin.option-contracts.send-demo', $c) }}" class="inline" onsubmit="return confirm('入居者の LINE にオプション内容と請求書PDFのリンクを送信しますか？');">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-md bg-slate-900 px-2.5 py-1 text-xs font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                                            @unless($hasPdf) disabled title="請求書PDFを登録してください"@endunless>送信</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">契約がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $contracts->links() }}</div>
@endsection
