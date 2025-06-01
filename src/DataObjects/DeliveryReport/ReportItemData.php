<?php

namespace ClassAtlas\USms\DataObjects\DeliveryReport;

use ClassAtlas\USms\Enums\USmsStatus;
use Spatie\LaravelData\Data;

class ReportItemData extends Data
{
    public function __construct(
        public string $number,
        public USmsStatus $statusID,
    ) {}
}
