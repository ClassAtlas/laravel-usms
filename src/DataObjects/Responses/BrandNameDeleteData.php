<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use Spatie\LaravelData\Data;

class BrandNameDeleteData extends Data
{
    public function __construct(
        public ?string $brandID = null,
        public string $message = '',
    ) {}
}
