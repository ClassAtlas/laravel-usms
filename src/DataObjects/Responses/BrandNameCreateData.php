<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use ClassAtlas\USms\Enums\USmsStatus;
use Spatie\LaravelData\Data;

class BrandNameCreateData extends Data
{
    public function __construct(
        public USmsStatus $statusID,
        public ?int $brandID,
        public string $message,
    ) {}
}
