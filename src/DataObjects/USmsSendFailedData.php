<?php

namespace ClassAtlas\USms\DataObjects;

use ClassAtlas\USms\Contracts\ResponseCode;
use Spatie\LaravelData\Data;

class USmsSendFailedData extends Data
{
    public function __construct(
        public ?string $notificationId,
        public ?string $notificationType,
        public int $statusID,
        public string $message,
        /**
         * Null when uBill refused the send with a statusID its docs do not list.
         */
        public ?ResponseCode $code = null,
        /**
         * Seconds until the send may be tried again, when uBill says: a login
         * code refused for its cooldown.
         */
        public ?int $retryAfter = null,
    ) {}
}
