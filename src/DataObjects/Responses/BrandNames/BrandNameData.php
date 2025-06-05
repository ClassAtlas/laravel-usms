<?php

namespace ClassAtlas\USms\DataObjects\Responses\BrandNames;

use ClassAtlas\USms\Enums\USmsStatus;
use Spatie\LaravelData\Data;

class BrandNameData extends Data
{
    public function __construct(
        public USmsStatus $statusID,
        /** @var BrandNameItemData[] */
        public array $brands,
    ) {}
}
