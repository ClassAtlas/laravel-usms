<?php

namespace ClassAtlas\USms\DataObjects;

use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use Spatie\LaravelData\Data;

class USmsWasSentData extends Data
{
    public function __construct(
        public ?string $notificationId,
        public SendSmsData $sendSmsData,
    ) {}
}
