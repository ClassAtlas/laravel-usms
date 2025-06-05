<?php

namespace ClassAtlas\USms\DataObjects;

use Illuminate\Support\Optional;
use Spatie\LaravelData\Data;

class MessageData extends Data
{
    public string|Optional $notificationId;

    public function __construct(
        public int    $brandId,
        public array  $numbers,
        public string $text,
        public bool   $stopList,
    )
    {
        $this->notificationId = uniqid();
    }
}
