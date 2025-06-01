<?php

namespace ClassAtlas\USms\DataObjects\DeliveryReport;

use ClassAtlas\USms\Enums\USmsStatus;
use Spatie\LaravelData\Data;

class ReportData extends Data
{
    public function __construct(
        public USmsStatus $statusID,
        /** @var ReportItemData[] */
        public array $result,
    ) {}
}
