<?php

namespace ClassAtlas\USms\Enums;

enum DeliveryStatus: int
{
    case SENT = 0;
    case RECEIVED = 1;
    case NOT_DELIVERED = 2;
    case AWAITING_STATUS = 3;
    case ERROR = 4;

    /**
     * Whether the provider will report anything further for this message.
     * SENT and AWAITING_STATUS are still in flight.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::RECEIVED, self::NOT_DELIVERED, self::ERROR => true,
            self::SENT, self::AWAITING_STATUS => false,
        };
    }

    public function isDelivered(): bool
    {
        return $this === self::RECEIVED;
    }
}
