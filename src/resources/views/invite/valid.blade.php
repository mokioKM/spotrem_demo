<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>招待 — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-12 text-slate-900">
<div class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
    <h1 class="text-lg font-semibold">招待が有効です</h1>
    <p class="mt-4 text-sm text-slate-600">
        LINE の LIFF アプリから本アプリにログインし、<code class="rounded bg-slate-100 px-1 text-xs">POST /api/invite/register</code> に
        <code class="rounded bg-slate-100 px-1 text-xs">Authorization: Bearer &lt;IDトークン&gt;</code> と次の JSON を送信してください。
    </p>
    <pre class="mt-4 overflow-x-auto rounded-md bg-slate-900 p-3 text-xs text-slate-100">{"token":"{{ $token }}"}</pre>
</div>
</body>
</html>
