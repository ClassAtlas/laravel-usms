<?php

use ClassAtlas\USms\DataObjects\Responses\SmsBalanceData;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Requests\SmsBalanceRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves GET /v1/sms/balance', function (): void {
    $request = new SmsBalanceRequest;

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/balance');
});

it('hydrates a SmsBalanceData DTO from the recorded fixture', function (): void {
    $this->connector->withMockClient(new MockClient([
        SmsBalanceRequest::class => scrubbedFixture('sms-balance'),
    ]));

    $balance = $this->sdk->balance();

    expect($balance)->toBeInstanceOf(SmsBalanceData::class)
        ->and($balance->sms)->toBeGreaterThanOrEqual(0);
});

it('authenticates with the key header', function (): void {
    $mock = new MockClient([SmsBalanceRequest::class => MockResponse::make(['statusID' => 0, 'sms' => 10])]);
    $this->connector->withMockClient($mock);

    $this->sdk->balance();

    $mock->assertSent(fn ($request, $response): bool => $response->getPendingRequest()->headers()->get('key') === 'test-api-key');
});

it('throws USmsException on a bad api key', function (): void {
    $this->connector->withMockClient(new MockClient([
        SmsBalanceRequest::class => MockResponse::make(
            ['statusID' => 401, 'message' => 'Invalid API key or permission denied', 'data' => null],
            401,
        ),
    ]));

    expect(fn () => $this->sdk->balance())->toThrow(USmsException::class, 'Invalid API key or permission denied');
});
