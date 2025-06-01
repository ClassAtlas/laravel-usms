<?php

namespace ClassAtlas\USms\DataObjects\BrandNames;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\Enums\BrandNameAuthorized;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class BrandNameItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public BrandNameAuthorized $authorized,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s')]
        public CarbonImmutable $createdAt,
    ) {}
}
