<?php

namespace ClassAtlas\USms\DataObjects;

use ClassAtlas\USms\Enums\USmsStatus;
use Spatie\LaravelData\Data;

class SendSmsData extends Data
{
    public function __construct(
        public USmsStatus $statusID,
        public ?int $smsID,
        public string $message,
    ) {}
}
