<?php

namespace ClassAtlas\USms\DataObjects;

use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use Spatie\LaravelData\Data;

class USmsWasSentData extends Data
{
    public function __construct(
        public ?string $notificationId,
        public ?SendSmsData $sendSmsData,
        public ?string $notificationType = null,
        /**
         * How many SMS parts this send cost, so that listeners tallying usage
         * count the same unit the platform is billed in. A Georgian message
         * over 70 characters is more than one part.
         *
         * Nullable because the sender is the only thing that knows the text:
         * anything constructing this payload without one leaves the count to
         * the listener's fallback.
         */
        public ?int $parts = null,
    ) {}
}
