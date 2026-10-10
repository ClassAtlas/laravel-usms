<?php

namespace ClassAtlas\USms\Exceptions;

use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use RuntimeException;
use Throwable;

class USmsException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?ErrorData $error = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromError(ErrorData $error): self
    {
        return match (true) {
            $error->isRateLimited() => new USmsRateLimitedException($error->message, $error, 429),
            $error->isRejection() => new USmsRejectedException($error->message, $error, (int) $error->statusID),
            default => new self($error->message, $error, $error->httpStatus),
        };
    }
}
