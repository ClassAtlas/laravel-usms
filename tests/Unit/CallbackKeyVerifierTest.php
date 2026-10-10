<?php

use ClassAtlas\USms\CallbackKeyVerifier;
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;

it('accepts the configured key', function (): void {
    (new CallbackKeyVerifier('secret'))->verify('secret');
})->throwsNoExceptions();

it('rejects a wrong or missing key', function (string $given): void {
    expect(fn () => (new CallbackKeyVerifier('secret'))->verify($given))
        ->toThrow(InvalidCallbackKeyException::class);
})->with(['wrong' => 'not-the-secret', 'empty' => '']);

it('rejects everything while no key is configured', function (string $given): void {
    expect(fn () => (new CallbackKeyVerifier(''))->verify($given))
        ->toThrow(InvalidCallbackKeyException::class, 'not configured');
})->with(['empty' => '', 'anything' => 'secret']);
