<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use Spatie\LaravelData\Data;

class BrandNameCreateData extends Data
{
    public function __construct(
        public int $brandID,
        public string $message = '',
    ) {}
}
