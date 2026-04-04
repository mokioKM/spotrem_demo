<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // LINE Login: ID トークン検証（LIFF チャンネル ID = verify の client_id）
    'line' => [
        'liff_channel_id' => env('LINE_LIFF_CHANNEL_ID'),
        // 入居者登録ページ用 LIFF（リッチメニュー用 URL と一致させる）
        'liff_id_register' => env('LINE_LIFF_ID'),
        // トラブル報告ページ用 LIFF。未設定時は LINE_LIFF_ID（単一 LIFF の後方互換）
        'liff_id_trouble' => env('LINE_LIFF_ID_TROUBLE') ?: env('LINE_LIFF_ID'),
        // 互換用（liff_id_register と同じ）
        'liff_id' => env('LINE_LIFF_ID'),
        // Messaging API（プッシュ・Webhook 署名検証）。チャネルは LIFF と同一またはリンクした Bot チャネル
        'messaging_channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'messaging_channel_secret' => env('LINE_CHANNEL_SECRET'),
    ],

    /*
    | Google Calendar（業者空き枠）。サービスアカウント JSON の絶対パスまたは base_path 相対。
    | 対象カレンダーを SA のメールアドレスに「閲覧権限」で共有すること。
    */
    'google' => [
        'calendar_credentials_path' => env('GOOGLE_CALENDAR_CREDENTIALS_PATH'),
        // Render 等: サービスアカウント JSON を base64（改行なし）で渡すとファイル不要。設定時は PATH より優先
        'calendar_credentials_base64' => env('GOOGLE_CALENDAR_CREDENTIALS_BASE64'),
        'calendar_slot_title_keyword' => env('GOOGLE_CALENDAR_SLOT_TITLE_KEYWORD', '対応可能'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'trouble_upload_folder' => env('CLOUDINARY_TROUBLE_FOLDER', 'spotrem/trouble'),
        'trouble_upload_max_kb' => (int) env('CLOUDINARY_TROUBLE_MAX_KB', 51200),
        // オプション請求書 PDF（resource_type: raw）
        'invoice_upload_folder' => env('CLOUDINARY_INVOICE_FOLDER', 'spotrem/invoices'),
        'invoice_upload_max_kb' => (int) env('CLOUDINARY_INVOICE_MAX_KB', 20480),
    ],

];
