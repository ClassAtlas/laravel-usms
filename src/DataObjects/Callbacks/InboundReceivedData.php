<?php

namespace ClassAtlas\USms\DataObjects\Callbacks;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;
use ClassAtlas\USms\Traits\ReadsInboundMessage;
use Spatie\LaravelData\Data;

/**
 * The `sms.inbound.received` callback: a message sent to the company's short
 * number. uBill delivers it at least once, so the same `eventID` can arrive
 * again and should be accepted without being stored twice.
 */
class InboundReceivedData extends Data
{
    use ReadsInboundMessage;

    public function __construct(
        public string $eventID,
        /**
         * The sender, in the local nine-digit format (`5XXXXXXXX`).
         */
        public string $from,
        /**
         * The short number it was sent to.
         */
        public string $to,
        public string $text,
        /**
         * The short number's "no SMS" policy, as uBill's administrator set it.
         */
        public bool $noSms,
        /**
         * When the operator sent it, in UTC.
         */
        public ?CarbonImmutable $sendAt = null,
    ) {}

    /**
     * @param  array<mixed>  $body
     *
     * @throws InvalidCallbackPayloadException
     */
    public static function fromCallback(array $body, string $event): self
    {
        $fields = self::readInboundMessage($body);

        if (is_string($fields)) {
            throw InvalidCallbackPayloadException::forParameter($event, $fields);
        }

        return new self(...$fields);
    }
}
