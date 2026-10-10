<?php

namespace ClassAtlas\USms;

use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;

class CallbackKeyVerifier
{
    public function __construct(private readonly string $key) {}

    /**
     * @throws InvalidCallbackKeyException
     */
    public function verify(string $given): void
    {
        if ($this->key === '') {
            throw new InvalidCallbackKeyException('uBill callback key is not configured.');
        }

        if (! hash_equals($this->key, $given)) {
            throw new InvalidCallbackKeyException;
        }
    }
}
