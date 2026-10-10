<?php

namespace ClassAtlas\USms\Exceptions;

class USmsRateLimitedException extends USmsException
{
    /**
     * Seconds uBill asked us to wait, when it said.
     */
    public function retryAfter(): ?int
    {
        return $this->error?->retryAfter;
    }
}
