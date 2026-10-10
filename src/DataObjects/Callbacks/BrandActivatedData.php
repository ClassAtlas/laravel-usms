<?php

namespace ClassAtlas\USms\DataObjects\Callbacks;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\Enums\BrandNameAuthorized;
use Spatie\LaravelData\Data;

class BrandActivatedData extends Data
{
    public function __construct(
        public int $brandID,
        public string $brandName,
        public ?BrandNameAuthorized $authorized = null,
        public ?CarbonImmutable $date = null,
    ) {}
}
