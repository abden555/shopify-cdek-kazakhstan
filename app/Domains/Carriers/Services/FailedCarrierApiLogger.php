<?php

namespace App\Domains\Carriers\Services;

use App\Models\FailedApiLog;
use Throwable;

final class FailedCarrierApiLogger
{
    /** @param array<string, mixed> $payload */
    public function log(string $operation, string $method, string $url, array $payload, Throwable $exception, ?int $status = null, ?string $response = null): void
    {
        report($exception);

        try {
            FailedApiLog::query()->create([
                'service' => 'cdek',
                'operation' => $operation,
                'request_method' => $method,
                'request_url' => $url,
                'request_payload' => $payload,
                'response_code' => $status,
                'response_body' => $response,
                'error_message' => $exception->getMessage(),
            ]);
        } catch (Throwable $loggingException) {
            report($loggingException);
        }
    }
}
