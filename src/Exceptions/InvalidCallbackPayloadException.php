<?php

namespace ClassAtlas\USms\Exceptions;

class InvalidCallbackPayloadException extends USmsException
{
    public static function forParameter(string $event, string $parameter): self
    {
        return new self("uBill callback [{$event}] is missing a valid [{$parameter}].");
    }
}
