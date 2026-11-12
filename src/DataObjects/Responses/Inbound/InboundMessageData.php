<?php

namespace ClassAtlas\USms\DataObjects\Responses\Inbound;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\Traits\ReadsInboundMessage;
use Spatie\LaravelData\Data;
use UnexpectedValueException;

/**
 * A message someone sent to the company's short number.
 */
class InboundMessageData extends Data
{
    use ReadsInboundMessage;

    public function __construct(
        /**
         * uBill's own id for the message, the same in the API and in the
         * callback. Store it to recognise a message delivered twice.
         */
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
         * When the operator sent it, in UTC. Null for some older messages.
         */
        public ?CarbonImmutable $sendAt = null,
    ) {}

    /**
     * @param  array<mixed>  $message
     *
     * @throws UnexpectedValueException
     */
    public static function fromPayload(array $message): self
    {
        $fields = self::readInboundMessage($message);

        if (is_string($fields)) {
            throw new UnexpectedValueException("uBill sent an incoming message without a readable [{$fields}].");
        }

        return new self(...$fields);
    }
}
