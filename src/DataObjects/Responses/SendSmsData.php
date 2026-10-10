<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use Spatie\LaravelData\Data;

class SendSmsData extends Data
{
    public function __construct(
        public ?int $smsID = null,
        public string $message = '',
    ) {}
}
