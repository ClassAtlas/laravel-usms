<?php

namespace ClassAtlas\USms\DataObjects\Responses\Inbound;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Throwable;
use UnexpectedValueException;

/**
 * A message someone sent to the company's short number.
 */
class InboundMessageData extends Data
{
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
        $fields = self::readFields($message);

        if (is_string($fields)) {
            throw new UnexpectedValueException("uBill sent an incoming message without a readable [{$fields}].");
        }

        return new self(...$fields);
    }

    /**
     * The message's fields, or the name of the first one that cannot be read.
     * `sendAt` and `noSms` are read leniently: uBill may leave them out of
     * older messages, and one such message must not hide the rest of a page.
     *
     * @param  array<mixed>  $message
     * @return array{eventID: string, from: string, to: string, text: string, noSms: bool, sendAt: ?CarbonImmutable}|string
     */
    protected static function readFields(array $message): array|string
    {
        foreach (['eventID', 'from', 'to'] as $field) {
            if (! is_scalar($message[$field] ?? null) || (string) $message[$field] === '') {
                return $field;
            }
        }

        if (! is_string($message['text'] ?? null)) {
            return 'text';
        }

        $noSms = match (true) {
            ! isset($message['noSms']) => false,
            is_scalar($message['noSms']) => filter_var($message['noSms'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            default => null,
        };

        if ($noSms === null) {
            return 'noSms';
        }

        return [
            'eventID' => (string) $message['eventID'],
            'from' => (string) $message['from'],
            'to' => (string) $message['to'],
            'text' => $message['text'],
            'noSms' => $noSms,
            'sendAt' => self::utcMoment($message['sendAt'] ?? null),
        ];
    }

    private static function utcMoment(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, 'UTC')->setTimezone('UTC');
        } catch (Throwable) {
            return null;
        }
    }
}
