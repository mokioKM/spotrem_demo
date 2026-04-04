<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Line\LineIdTokenVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorization: Bearer に付与された LIFF の ID トークンを検証し、line_uid をリクエスト属性に格納する
 */
final class VerifyLineLiffToken
{
    public function __construct(
        private readonly LineIdTokenVerifier $verifier,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => __('認証トークンが必要です。')], 401);
        }

        $idToken = trim(substr($header, strlen('Bearer ')));
        if ($idToken === '') {
            return response()->json(['message' => __('認証トークンが不正です。')], 401);
        }

        try {
            $lineUid = $this->verifier->verify($idToken);
        } catch (\Throwable $e) {
            Log::error('LINE LIFF token verification failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            $message = $e instanceof \RuntimeException && str_contains($e->getMessage(), 'not configured')
                ? __('サーバー設定が不足しています。')
                : __('認証に失敗しました。');

            // 開発時: LINE の error_description を短く付与し、チャネルID誤設定などを切り分けしやすくする
            if (config('app.debug') && $e instanceof \InvalidArgumentException && $e->getMessage() !== '') {
                $message .= ' ['.$e->getMessage().']';
            }

            return response()->json(['message' => $message], 401);
        }

        $request->attributes->set('line_uid', $lineUid);

        return $next($request);
    }
}
