<?php

namespace ClassAtlas\USms\DataObjects\Responses\DeliveryReport;

use ClassAtlas\USms\Traits\HasDeliveryStatus;
use Spatie\LaravelData\Data;

class ReportItemData extends Data
{
    use HasDeliveryStatus;

    public function __construct(
        public string $number,
        public int $statusID,
    ) {}
}
