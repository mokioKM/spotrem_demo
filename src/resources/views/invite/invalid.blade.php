<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>招待エラー — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-12 text-slate-900">
<div class="mx-auto max-w-md rounded-lg border border-red-200 bg-red-50 p-8 shadow-sm">
    <h1 class="text-lg font-semibold text-red-900">招待を利用できません</h1>
    <p class="mt-4 text-sm text-red-800">{{ $reason }}</p>
</div>
</body>
</html>
