<?php

namespace ClassAtlas\USms\DataObjects\Responses\DeliveryReport;

use ClassAtlas\USms\Enums\USmsStatus;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class ReportData extends Data
{
    public function __construct(
        public USmsStatus $statusID,
        /** @var Collection<int, ReportItemData> */
        public Collection $result,
    )
    {
    }
}
