<?php

namespace ClassAtlas\USms\DataObjects\Callbacks;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\Traits\HasDeliveryStatus;
use Spatie\LaravelData\Data;

class DeliveryUpdatedData extends Data
{
    use HasDeliveryStatus;

    public function __construct(
        public int $smsID,
        public string $number,
        public int $statusID,
        public ?CarbonImmutable $date = null,
    ) {}
}
