<?php

namespace ClassAtlas\USms\DataObjects;

use DateTimeInterface;
use Spatie\LaravelData\Data;

class MessageData extends Data
{
    /**
     * @param  string[]  $numbers
     */
    public function __construct(
        public int $brandId,
        public array $numbers,
        public string $text,
        public bool $stopList = false,
        public bool $otp = false,
        public ?DateTimeInterface $sendAt = null,
        public ?string $callbackUrl = null,
        public ?string $notificationId = null,
    ) {}
}
