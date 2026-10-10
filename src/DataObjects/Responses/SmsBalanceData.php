<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use Spatie\LaravelData\Data;

class SmsBalanceData extends Data
{
    public function __construct(
        public int $sms,
    ) {}
}
