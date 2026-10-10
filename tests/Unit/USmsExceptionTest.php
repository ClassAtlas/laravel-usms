<?php

use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\Enums\SendSmsCode;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRateLimitedException;
use ClassAtlas\USms\Exceptions\USmsRejectedException;

it('maps a refused request to USmsRejectedException carrying its code', function (): void {
    $exception = USmsException::fromError(new ErrorData(
        message: 'Valid numbers not found',
        httpStatus: 200,
        statusID: 50,
        code: SendSmsCode::NO_VALID_NUMBERS,
    ));

    expect($exception)->toBeInstanceOf(USmsRejectedException::class)
        ->and($exception->responseCode())->toBe(SendSmsCode::NO_VALID_NUMBERS)
        ->and($exception->statusId())->toBe(50)
        ->and($exception->getMessage())->toBe('Valid numbers not found');
});

it('keeps the raw statusID when uBill refuses with a code its docs do not list', function (): void {
    $exception = USmsException::fromError(new ErrorData(message: 'Something new', httpStatus: 200, statusID: 77));

    expect($exception)->toBeInstanceOf(USmsRejectedException::class)
        ->and($exception->responseCode())->toBeNull()
        ->and($exception->statusId())->toBe(77);
});

it('maps a 429 to USmsRateLimitedException however uBill signals it', function (ErrorData $error): void {
    expect(USmsException::fromError($error))->toBeInstanceOf(USmsRateLimitedException::class);
})->with([
    'http status' => [new ErrorData(message: 'Too many requests', httpStatus: 429)],
    'body statusID' => [new ErrorData(message: 'Too many requests', httpStatus: 200, statusID: 429)],
]);

it('exposes the Retry-After uBill asked for', function (): void {
    $exception = USmsException::fromError(new ErrorData(message: 'Slow down', httpStatus: 429, retryAfter: 30));

    expect($exception)->toBeInstanceOf(USmsRateLimitedException::class)
        ->and($exception->retryAfter())->toBe(30);
});

it('maps every other failure to the base exception', function (ErrorData $error): void {
    $exception = USmsException::fromError($error);

    expect($exception::class)->toBe(USmsException::class)
        ->and($exception->getCode())->toBe($error->httpStatus);
})->with([
    'bad api key' => [new ErrorData(message: 'Invalid API key or permission denied', httpStatus: 401, statusID: 401)],
    'gateway timeout' => [new ErrorData(message: 'uBill responded with HTTP 524', httpStatus: 524)],
    'success status without a statusID' => [new ErrorData(message: 'uBill responded with HTTP 200', httpStatus: 200)],
]);
