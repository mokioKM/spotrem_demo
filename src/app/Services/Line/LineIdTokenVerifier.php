<?php

declare(strict_types=1);

namespace App\Services\Line;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * LINE Login の ID トークンを verify で検証し、sub（LINE UID）を返す
 *
 * @see https://developers.line.biz/en/reference/line-login/#verify-id-token
 */
final class LineIdTokenVerifier
{
    /**
     * @throws InvalidArgumentException トークン無効・検証失敗
     * @throws RuntimeException チャンネル ID 未設定など
     */
    public function verify(string $idToken): string
    {
        $rawClientId = config('services.line.liff_channel_id');
        $clientId = is_string($rawClientId) ? trim($rawClientId) : '';
        if ($clientId === '') {
            Log::error('LINE_LIFF_CHANNEL_ID is not configured');

            throw new RuntimeException('LINE LIFF channel is not configured.');
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://api.line.me/oauth2/v2.1/verify', [
                'id_token' => $idToken,
                'client_id' => $clientId,
            ]);

        if (! $response->ok()) {
            $json = $response->json();
            $lineError = is_array($json)
                ? (string) ($json['error_description'] ?? $json['error'] ?? $response->body())
                : $response->body();
            Log::warning('LINE id_token verify HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'line_error' => $lineError,
            ]);

            throw new InvalidArgumentException(mb_strimwidth($lineError, 0, 240, '…'));
        }

        $sub = $response->json('sub');
        if (! is_string($sub) || $sub === '') {
            Log::warning('LINE id_token verify response missing sub', ['json' => $response->json()]);

            throw new InvalidArgumentException('Invalid id_token payload');
        }

        return $sub;
    }
}
