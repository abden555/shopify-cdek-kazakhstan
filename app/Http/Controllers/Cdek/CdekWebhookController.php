<?php

namespace App\Http\Controllers\Cdek;

use App\Http\Controllers\Controller;
use App\Services\Cdek\CdekWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CdekWebhookController extends Controller
{
    public function __invoke(string $token, Request $request, CdekWebhookService $webhooks): Response
    {
        $configuredToken = (string) config('carriers.cdek.webhook_token');

        abort_unless($configuredToken !== '' && hash_equals($configuredToken, $token), 404);

        $webhooks->handle($request);

        return response()->noContent(202);
    }
}
