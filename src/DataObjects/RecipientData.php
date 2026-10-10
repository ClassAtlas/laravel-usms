<?php

namespace ClassAtlas\USms\DataObjects;

use Spatie\LaravelData\Data;

class RecipientData extends Data
{
    public function __construct(
        public string $number,
        public string $text,
    ) {}
}
