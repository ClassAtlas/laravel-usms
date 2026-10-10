<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use ClassAtlas\USms\Contracts\ResponseCode;

final readonly class ErrorData
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public function __construct(
        public string $message,
        public int $httpStatus,
        public ?int $statusID = null,
        public ?ResponseCode $code = null,
        public ?int $retryAfter = null,
        public ?array $details = null,
    ) {}

    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429 || $this->statusID === 429;
    }

    /**
     * uBill answers a refused request with HTTP 200 and a non-zero statusID.
     */
    public function isRejection(): bool
    {
        return $this->httpStatus >= 200
            && $this->httpStatus < 300
            && $this->statusID !== null
            && $this->statusID !== 0;
    }
}
