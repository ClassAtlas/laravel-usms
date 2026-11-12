<?php

namespace ClassAtlas\USms\Traits;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Reads one incoming message as uBill sends it, from the Incoming SMS API and
 * from the `sms.inbound.received` callback alike.
 */
trait ReadsInboundMessage
{
    /**
     * The message's fields, or the name of the first one that cannot be read.
     * `sendAt` is read leniently: uBill leaves it out for some older messages.
     *
     * @param  array<mixed>  $message
     * @return array{eventID: string, from: string, to: string, text: string, noSms: bool, sendAt: ?CarbonImmutable}|string
     */
    protected static function readInboundMessage(array $message): array|string
    {
        foreach (['eventID', 'from', 'to'] as $field) {
            if (! is_scalar($message[$field] ?? null) || (string) $message[$field] === '') {
                return $field;
            }
        }

        if (! is_string($message['text'] ?? null)) {
            return 'text';
        }

        $noSms = is_scalar($message['noSms'] ?? null)
            ? filter_var($message['noSms'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            : null;

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
