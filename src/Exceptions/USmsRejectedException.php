<?php

namespace ClassAtlas\USms\Exceptions;

use ClassAtlas\USms\Contracts\ResponseCode;

/**
 * uBill understood the request and refused it. Retrying the same request
 * gets the same answer.
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
}
