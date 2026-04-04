@extends('liff.layout')

@section('title', 'LIFF メニュー')

@section('content')
    <h1 class="liff-h1">SpotRem（入居者）</h1>
    <p class="liff-lead">本番ではリッチメニューから「入居者登録」「トラブル報告」をそれぞれ別 LIFF（別 URL）で開きます。このページは開発用の一覧です。</p>
    <ul class="liff-menu">
        <li>
            <a href="/liff/resident-register">入居者登録</a>
        </li>
        <li>
            <a href="/liff/trouble-report">トラブル報告</a>
        </li>
    </ul>
    <p class="liff-hint" style="margin-top:1rem">LINE Developers で LIFF を2つ作り、Endpoint を <code style="font-size:0.7rem">…/liff/resident-register</code> と <code style="font-size:0.7rem">…/liff/trouble-report</code> にそれぞれ合わせ、<code>.env</code> の <code>LINE_LIFF_ID</code> と <code>LINE_LIFF_ID_TROUBLE</code> に対応する LIFF ID を設定してください。</p>
@endsection
