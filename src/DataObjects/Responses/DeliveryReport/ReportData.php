<?php

namespace ClassAtlas\USms\DataObjects\Responses\DeliveryReport;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class ReportData extends Data
{
    public function __construct(
        /** @var Collection<int, ReportItemData> */
        public Collection $result,
    ) {}
}
