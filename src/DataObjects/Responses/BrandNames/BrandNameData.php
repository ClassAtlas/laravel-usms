<?php

namespace ClassAtlas\USms\DataObjects\Responses\BrandNames;

use Spatie\LaravelData\Data;

class BrandNameData extends Data
{
    public function __construct(
        /** @var BrandNameItemData[] */
        public array $brands,
    ) {}
}
