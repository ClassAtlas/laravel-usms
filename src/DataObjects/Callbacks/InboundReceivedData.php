<?php

namespace ClassAtlas\USms\DataObjects\Callbacks;

use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMessageData;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;

/**
 * The `sms.inbound.received` callback: a message sent to the company's short
 * number, with the same fields as one read from the Incoming SMS API. uBill
 * delivers it at least once, so the same `eventID` can arrive again and should
 * be accepted without being stored twice.
 */
class InboundReceivedData extends InboundMessageData
{
    /**
     * @param  array<mixed>  $body
     *
     * @throws InvalidCallbackPayloadException
     */
    public static function fromCallback(array $body, string $event): self
    {
        $fields = self::readFields($body);

        if (is_string($fields)) {
            throw InvalidCallbackPayloadException::forParameter($event, $fields);
        }

        return new self(...$fields);
    }
}
