<?php

use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\UnknownCallbackData;
use ClassAtlas\USms\Http\USmsCallbackRequest;
use Illuminate\Support\Facades\Route;

/**
 * @param  array<string, string>  $overrides
 */
function callbackUri(array $overrides = []): string
{
    return '/__test-usms-callback?'.http_build_query(array_merge([
        'key' => 'test-callback-key',
        'event' => 'sms.delivery.updated',
        'smsID' => '12345678',
        'number' => '995555000000',
        'statusID' => '1',
        'date' => '2026-02-01 12:02:02',
    ], $overrides));
}

beforeEach(function (): void {
    Route::get('/__test-usms-callback', function (USmsCallbackRequest $request) {
        $callback = $request->callback();

        return response()->json([
            'type' => $callback::class,
            'smsID' => $callback instanceof DeliveryUpdatedData ? $callback->smsID : null,
            'final' => $callback instanceof DeliveryUpdatedData ? $callback->isFinal() : null,
        ]);
    });
});

it('resolves the FormRequest and parses the DTO on a valid key', function (): void {
    $this->get(callbackUri())
        ->assertSuccessful()
        ->assertJson([
            'type' => DeliveryUpdatedData::class,
            'smsID' => 12345678,
            'final' => true,
        ]);
});

it('accepts the query string uBill really sends', function (): void {
    // Recorded from the live API; only the key and the number are replaced.
    $this->get('/__test-usms-callback?key=test-callback-key&event=sms.delivery.updated&smsID=5174243&number=555000000&statusID=1&date=2026-10-10%2015%3A35%3A53')
        ->assertSuccessful()
        ->assertJson([
            'type' => DeliveryUpdatedData::class,
            'smsID' => 5174243,
            'final' => true,
        ]);
});

it('aborts with 403 when the key is wrong or missing', function (array $overrides): void {
    $this->get(callbackUri($overrides))->assertForbidden();
})->with([
    'wrong' => [['key' => 'not-the-key']],
    'empty' => [['key' => '']],
]);

it('aborts with 403 for every callback while no key is configured', function (): void {
    config()->set('usms.callback_key', '');

    $this->get(callbackUri(['key' => '']))->assertForbidden();
});

it('aborts with 422 when a known event is malformed', function (): void {
    $this->get(callbackUri(['smsID' => 'abc']))->assertUnprocessable();
});

it('acknowledges an event it does not model', function (): void {
    $this->get(callbackUri(['event' => 'sms.something.new']))
        ->assertSuccessful()
        ->assertJson(['type' => UnknownCallbackData::class]);
});
