<?php

namespace ClassAtlas\USms\DataObjects;

use DateTimeInterface;
use Spatie\LaravelData\Data;

class PersonalizedMessageData extends Data
{
    /**
     * @param  RecipientData[]  $recipients
     */
    public function __construct(
        public int $brandId,
        public array $recipients,
        public bool $stopList = false,
        public bool $otp = false,
        public ?DateTimeInterface $sendAt = null,
        public ?string $callbackUrl = null,
    ) {}
}
