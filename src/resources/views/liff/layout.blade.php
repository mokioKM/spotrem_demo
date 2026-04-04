<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SpotRem') — {{ config('app.name') }}</title>
    {{-- ngrok 無料版は <link> のサブリクエストにも警告用 HTML を返し CSS が無効になる。fetch 用ヘッダは付けられないためインライン化する --}}
    @php
        $liffCssPath = public_path('css/liff.css');
        $liffCss = is_readable($liffCssPath) ? (string) file_get_contents($liffCssPath) : '';
    @endphp
    @if ($liffCss !== '')
        <style>{!! $liffCss !!}</style>
    @endif
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
</head>
<body class="liff-body">
<main class="liff-main">
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
