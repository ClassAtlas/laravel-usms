<?php

namespace ClassAtlas\USms\Exceptions;

class InvalidCallbackKeyException extends USmsException
{
    public function __construct(string $reason = 'Callback key verification failed.')
    {
        parent::__construct($reason);
    }
}
