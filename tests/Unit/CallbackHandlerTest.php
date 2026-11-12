<?php

use ClassAtlas\USms\CallbackHandler;
use ClassAtlas\USms\CallbackKeyVerifier;
use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\InboundReceivedData;
use ClassAtlas\USms\DataObjects\Callbacks\UnknownCallbackData;
use ClassAtlas\USms\Enums\BrandNameAuthorized;
use ClassAtlas\USms\Enums\DeliveryStatus;
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;

function callbackHandler(): CallbackHandler
{
    return new CallbackHandler(new CallbackKeyVerifier('secret'), 'Asia/Tbilisi');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function deliveryCallback(array $overrides = []): array
{
    return array_merge([
        'key' => 'secret',
        'event' => 'sms.delivery.updated',
        'smsID' => '12345678',
        'number' => '995555000000',
        'statusID' => '1',
        'date' => '2026-02-01 12:02:02',
    ], $overrides);
}

it('parses a delivery update', function (): void {
    $callback = callbackHandler()->handle(deliveryCallback());

    expect($callback)->toBeInstanceOf(DeliveryUpdatedData::class)
        ->and($callback->smsID)->toBe(12345678)
        ->and($callback->number)->toBe('995555000000')
        ->and($callback->statusID)->toBe(1)
        ->and($callback->status())->toBe(DeliveryStatus::RECEIVED)
        ->and($callback->isFinal())->toBeTrue()
        ->and($callback->date->toIso8601String())->toBe('2026-02-01T12:02:02+04:00');
});

it('parses a delivery callback exactly as uBill sends it', function (): void {
    // Recorded from the live API; only the key and the number are replaced.
    parse_str(
        'key=secret&event=sms.delivery.updated&smsID=5174243&number=555000000&statusID=1&date=2026-10-10%2015%3A35%3A53',
        $query,
    );

    $callback = callbackHandler()->handle($query);

    expect($callback)->toBeInstanceOf(DeliveryUpdatedData::class)
        ->and($callback->smsID)->toBe(5174243)
        ->and($callback->number)->toBe('555000000')
        ->and($callback->status())->toBe(DeliveryStatus::RECEIVED)
        ->and($callback->date->toIso8601String())->toBe('2026-10-10T15:35:53+04:00');
});

it('keeps a status uBill does not document, as not final', function (): void {
    $callback = callbackHandler()->handle(deliveryCallback(['statusID' => '9']));

    expect($callback->statusID)->toBe(9)
        ->and($callback->status())->toBeNull()
        ->and($callback->isFinal())->toBeFalse();
});

it('keeps a delivery update whose date is missing or unreadable', function (mixed $date): void {
    $callback = callbackHandler()->handle(deliveryCallback(['date' => $date]));

    expect($callback)->toBeInstanceOf(DeliveryUpdatedData::class)
        ->and($callback->date)->toBeNull();
})->with(['missing' => null, 'empty' => '', 'another format' => '01/02/2026 12:02']);

it('parses a brand activation', function (): void {
    $callback = callbackHandler()->handle([
        'key' => 'secret',
        'event' => 'sms.brand.activated',
        'brandID' => '12',
        'brandName' => 'MyBrand',
        'authorized' => '1',
        'date' => '2026-08-10 15:30:00',
    ]);

    expect($callback)->toBeInstanceOf(BrandActivatedData::class)
        ->and($callback->brandID)->toBe(12)
        ->and($callback->brandName)->toBe('MyBrand')
        ->and($callback->authorized)->toBe(BrandNameAuthorized::AUTHORIZED);
});

it('hands back an event it does not model, without the key', function (?string $event): void {
    $callback = callbackHandler()->handle(array_filter([
        'key' => 'secret',
        'event' => $event,
        'foo' => 'bar',
    ], fn (mixed $value): bool => $value !== null));

    expect($callback)->toBeInstanceOf(UnknownCallbackData::class)
        ->and($callback->event)->toBe($event)
        ->and($callback->payload)->not->toHaveKey('key')
        ->and($callback->payload)->toHaveKey('foo', 'bar');
})->with(['a future event' => 'sms.something.new', 'no event at all' => null]);

it('verifies the key before reading anything else', function (array $query): void {
    expect(fn () => callbackHandler()->handle($query))
        ->toThrow(InvalidCallbackKeyException::class);
})->with([
    'wrong key' => [deliveryCallback(['key' => 'nope'])],
    'missing key' => [array_diff_key(deliveryCallback(), ['key' => true])],
    'key as array' => [deliveryCallback(['key' => ['secret']])],
]);

it('refuses a known event with a malformed payload', function (array $overrides, string $parameter): void {
    expect(fn () => callbackHandler()->handle(deliveryCallback($overrides)))
        ->toThrow(InvalidCallbackPayloadException::class, "[{$parameter}]");
})->with([
    'missing smsID' => [['smsID' => null], 'smsID'],
    'non-numeric smsID' => [['smsID' => 'abc'], 'smsID'],
    'missing number' => [['number' => ''], 'number'],
    'non-numeric statusID' => [['statusID' => 'delivered'], 'statusID'],
    'negative statusID' => [['statusID' => '-1'], 'statusID'],
]);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function inboundCallback(array $overrides = []): array
{
    return array_merge([
        'eventID' => '29a24670-e631-4bf3-b593-ea35df838115',
        'from' => '555000000',
        'to' => '90566',
        'text' => 'Example incoming SMS',
        'noSms' => false,
        'sendAt' => '2026-11-02T08:30:00.396288Z',
        'event' => 'sms.inbound.received',
        'key' => 'secret',
    ], $overrides);
}

it('parses an incoming message posted as JSON', function (): void {
    $callback = callbackHandler()->handleJson(inboundCallback(), 'secret');

    expect($callback)->toBeInstanceOf(InboundReceivedData::class)
        ->and($callback->eventID)->toBe('29a24670-e631-4bf3-b593-ea35df838115')
        ->and($callback->from)->toBe('555000000')
        ->and($callback->to)->toBe('90566')
        ->and($callback->text)->toBe('Example incoming SMS')
        ->and($callback->noSms)->toBeFalse()
        ->and($callback->sendAt->format('Y-m-d\TH:i:s.uP'))->toBe('2026-11-02T08:30:00.396288+00:00');
});

it('reads the key from the body when the header is missing', function (): void {
    expect(callbackHandler()->handleJson(inboundCallback()))->toBeInstanceOf(InboundReceivedData::class);
});

it('rejects a JSON callback whose header or body key is wrong', function (array $body, ?string $token): void {
    callbackHandler()->handleJson($body, $token);
})->with([
    'wrong header, right body' => [inboundCallback(), 'not-the-key'],
    'no header, wrong body' => [inboundCallback(['key' => 'not-the-key']), null],
    'no key at all' => [array_diff_key(inboundCallback(), ['key' => true]), null],
])->throws(InvalidCallbackKeyException::class);

it('rejects an incoming message missing a field it needs', function (string $field, mixed $value): void {
    callbackHandler()->handleJson(inboundCallback([$field => $value]), 'secret');
})->with([
    'eventID' => ['eventID', ''],
    'from' => ['from', null],
    'to' => ['to', ['90566']],
    'text' => ['text', null],
    'noSms' => ['noSms', 'perhaps'],
])->throws(InvalidCallbackPayloadException::class);

it('keeps an incoming message whose send time cannot be read', function (): void {
    expect(callbackHandler()->handleJson(inboundCallback(['sendAt' => 'not a time']), 'secret')->sendAt)->toBeNull();
});

it('hands over a JSON event it does not model, without the key', function (): void {
    $callback = callbackHandler()->handleJson(['event' => 'sms.something.new', 'key' => 'secret', 'id' => 7], 'secret');

    expect($callback)->toBeInstanceOf(UnknownCallbackData::class)
        ->and($callback->event)->toBe('sms.something.new')
        ->and($callback->payload)->toBe(['event' => 'sms.something.new', 'id' => 7]);
});

it('does not read an incoming message from a query string', function (): void {
    $callback = callbackHandler()->handle(['key' => 'secret', 'event' => 'sms.inbound.received', 'eventID' => 'a']);

    expect($callback)->toBeInstanceOf(UnknownCallbackData::class);
});
