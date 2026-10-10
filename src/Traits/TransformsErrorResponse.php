<?php

namespace ClassAtlas\USms\Traits;

use ClassAtlas\USms\Contracts\ResponseCode;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use Saloon\Http\Response;

trait TransformsErrorResponse
{
    /**
     * Null when the response is a success: a 2xx carrying `statusID: 0`.
     * Tolerates non-JSON bodies, which Cloudflare sends on a gateway timeout.
     */
    protected function errorFromResponse(Response $response): ?ErrorData
    {
        $payload = json_decode((string) $response->body(), true);
        $payload = is_array($payload) ? $payload : null;

        $statusId = is_numeric($payload['statusID'] ?? null) ? (int) $payload['statusID'] : null;

        if ($response->successful() && $statusId === 0) {
            return null;
        }

        $message = $payload['message'] ?? null;
        $retryAfter = $response->header('Retry-After');

        return new ErrorData(
            message: is_string($message) && $message !== ''
                ? $message
                : 'uBill responded with HTTP '.$response->status(),
            httpStatus: $response->status(),
            statusID: $statusId,
            code: $response->successful() && $statusId !== null ? $this->responseCode($statusId) : null,
            retryAfter: is_numeric($retryAfter) ? (int) $retryAfter : null,
            details: $payload,
        );
    }

    /**
     * The endpoint's own meaning for a non-zero statusID.
     */
    protected function responseCode(int $statusId): ?ResponseCode
    {
        return null;
    }
}
