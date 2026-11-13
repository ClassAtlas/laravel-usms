<?php

namespace ClassAtlas\USms\Exceptions;

use ClassAtlas\USms\Contracts\ResponseCode;
use ClassAtlas\USms\DataObjects\Responses\RejectedNumberData;

/**
 * uBill understood the request and refused it. Retrying the same request
 * gets the same answer, except a login code refused for its cooldown
 * (`SendSmsCode::OTP_RECIPIENT_LIMITED`), which may be sent once
 * `retryAfter()` has passed.
 */
class USmsRejectedException extends USmsException
{
    /**
     * Null when uBill answered with a statusID its docs do not list.
     */
    public function responseCode(): ?ResponseCode
    {
        return $this->error?->code;
    }

    public function statusId(): int
    {
        return $this->getCode();
    }

    /**
     * Seconds until the first refused recipient may be sent to again: uBill's
     * `Retry-After` header, else the shortest wait among the refused numbers.
     * Null when uBill gives no wait.
     */
    public function retryAfter(): ?int
    {
        if ($this->error?->retryAfter !== null) {
            return $this->error->retryAfter;
        }

        $waits = array_values(array_filter(
            array_map(fn (RejectedNumberData $number): ?int => $number->retryAfter, $this->rejectedNumbers()),
            fn (?int $wait): bool => $wait !== null,
        ));

        return $waits === [] ? null : min($waits);
    }

    /**
     * The recipients uBill refused, when it named them.
     *
     * @return list<RejectedNumberData>
     */
    public function rejectedNumbers(): array
    {
        return RejectedNumberData::listFrom($this->error?->details['rejectedNumbers'] ?? null);
    }
}
