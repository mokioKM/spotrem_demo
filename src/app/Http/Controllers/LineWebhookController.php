<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Line\LineWebhookInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LINE Messaging API Webhook 受信（署名検証はミドルウェア）。
 * テキストメッセージは「チャット返信非対応」の定型文で reply する。
 */
final class LineWebhookController extends Controller
{
    public function __construct(
        private readonly LineWebhookInboundService $webhookInboundService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $events = $request->input('events', []);
        if (! is_array($events)) {
            return response()->json([]);
        }

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $type = $event['type'] ?? 'unknown';
            Log::info('LINE webhook event received', [
                'type' => $type,
                'source' => $event['source'] ?? null,
            ]);
        }

        $this->webhookInboundService->handleEvents($events);

        return response()->json([]);
    }
}
