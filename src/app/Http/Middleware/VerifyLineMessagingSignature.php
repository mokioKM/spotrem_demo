<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * LINE Messaging API の Webhook 署名（X-Line-Signature）検証
 *
 * @see https://developers.line.biz/en/reference/messaging-api/#signature-validation
 */
final class VerifyLineMessagingSignature
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.line.messaging_channel_secret');
        if (! is_string($secret) || $secret === '') {
            throw new HttpException(503, 'LINE channel secret is not configured.');
        }

        $signature = $request->header('X-Line-Signature');
        if (! is_string($signature) || $signature === '') {
            throw new HttpException(403, 'Missing signature.');
        }

        $body = $request->getContent();
        $hash = hash_hmac('sha256', $body, $secret, true);
        $expected = base64_encode($hash);

        if (! hash_equals($expected, $signature)) {
            throw new HttpException(403, 'Invalid signature.');
        }

        return $next($request);
    }
}
