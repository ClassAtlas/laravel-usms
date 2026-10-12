<?php

namespace ClassAtlas\USms\DataObjects;

use DateTimeInterface;
use Spatie\LaravelData\Data;

class PersonalizedMessageData extends Data
{
    /**
     * @param  RecipientData[]  $recipients
     * @param  int|null  $timeout  seconds this one request may wait for uBill's answer; the connector's own when null
     */
    public function __construct(
        public int $brandId,
        public array $recipients,
        public bool $stopList = false,
        public bool $otp = false,
        public ?DateTimeInterface $sendAt = null,
        public ?string $callbackUrl = null,
        public ?int $timeout = null,
    ) {}
}
