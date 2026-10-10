<?php

namespace ClassAtlas\USms\Traits;

use ClassAtlas\USms\Enums\DeliveryStatus;

/**
 * For a DTO that carries uBill's raw `statusID` of a message.
 */
trait HasDeliveryStatus
{
    /**
     * Null when uBill reports a status its docs do not list.
     */
    public function status(): ?DeliveryStatus
    {
        return DeliveryStatus::tryFrom($this->statusID);
    }

    public function isFinal(): bool
    {
        return $this->status()?->isFinal() ?? false;
    }
}
