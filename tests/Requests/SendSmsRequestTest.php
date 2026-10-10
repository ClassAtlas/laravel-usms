<?php

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\MessageData;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\Enums\SendSmsCode;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRateLimitedException;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves POST /v1/sms/send', function (): void {
    $request = new SendSmsRequest(testMessage());

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/send');
});

it('sends only the four original fields when no option is set', function (): void {
    $request = new SendSmsRequest(new MessageData(29, ['995555000000'], 'Hello'));

    expect($request->body()->all())->toBe([
        'brandID' => 29,
        'numbers' => ['995555000000'],
        'text' => 'Hello',
        'stopList' => false,
    ]);
});

it('adds otp, callbackUrl and a sendTime written in the provider timezone', function (): void {
    $request = new SendSmsRequest(new MessageData(
        brandId: 29,
        numbers: ['995555000000'],
        text: 'Hello',
        stopList: true,
        otp: true,
        sendAt: CarbonImmutable::parse('2026-02-01 19:30:00', 'UTC'),
        callbackUrl: 'https://example.test/usms/callback',
    ), 'Asia/Tbilisi');

    expect($request->body()->all())->toBe([
        'brandID' => 29,
        'numbers' => ['995555000000'],
        'text' => 'Hello',
        'stopList' => true,
        'otp' => true,
        'sendTime' => '2026-02-01 23:30',
        'callbackUrl' => 'https://example.test/usms/callback',
    ]);
});

it('hydrates SendSmsData from the recorded fixture', function (string $fixture, array $options): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => scrubbedFixture($fixture),
    ]));

    $response = $this->sdk->send(testMessage('ClassAtlas test', ...$options));

    expect($response)->toBeInstanceOf(SendSmsData::class)
        ->and($response->smsID)->toBeGreaterThan(0);
})->with([
    'plain' => ['send-sms', []],
    'otp' => ['send-sms-otp', ['otp' => true]],
    'callback url' => ['send-sms-callback-url', ['callbackUrl' => 'https://example.test/usms/callback']],
    'scheduled' => ['send-sms-scheduled', ['sendAt' => new CarbonImmutable('2026-10-10 09:32:00', 'Asia/Tbilisi')]],
    // uBill does not refuse a send time that has passed: it sends at once.
    'send time in the past' => ['send-sms-past-send-time', ['sendAt' => new CarbonImmutable('2026-10-09 09:28:00', 'Asia/Tbilisi')]],
]);

it('accepts a success answer that carries no smsID', function (array $body): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make($body),
    ]));

    $response = $this->sdk->send(testMessage());

    expect($response)->toBeInstanceOf(SendSmsData::class)
        ->and($response->smsID)->toBeNull();
})->with([
    'smsID missing' => [['statusID' => 0, 'message' => 'SMS Sent']],
    'smsID null' => [['statusID' => 0, 'smsID' => null, 'message' => 'SMS Sent']],
]);

it('throws USmsRejectedException with the send code when uBill refuses', function (int $statusId, string $message, SendSmsCode $code): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make(['statusID' => $statusId, 'smsID' => null, 'message' => $message]),
    ]));

    try {
        $this->sdk->send(testMessage());
    } catch (USmsRejectedException $e) {
        expect($e->responseCode())->toBe($code)
            ->and($e->statusId())->toBe($statusId)
            ->and($e->getMessage())->toBe($message);

        return;
    }

    $this->fail('Expected a USmsRejectedException.');
})->with([
    'unknown brand' => [10, 'brandID not found or not authorized', SendSmsCode::BRAND_NOT_FOUND],
    'no numbers' => [20, 'Numbers not found', SendSmsCode::NUMBERS_NOT_FOUND],
    'empty text' => [30, 'Empty message text', SendSmsCode::EMPTY_TEXT],
    'no balance' => [40, 'Not enough SMS', SendSmsCode::INSUFFICIENT_BALANCE],
    'no valid numbers' => [50, 'Valid numbers not found', SendSmsCode::NO_VALID_NUMBERS],
    'invalid send time' => [60, 'Invalid sendTime format. Use Y-m-d H:i', SendSmsCode::INVALID_SEND_TIME],
    'json error' => [90, 'Json Error', SendSmsCode::JSON_ERROR],
    'general error' => [99, 'General error', SendSmsCode::GENERAL_ERROR],
]);

it('does not crash on a refusal code uBill has not documented', function (): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make(['statusID' => 77, 'smsID' => null, 'message' => 'Something new']),
    ]));

    try {
        $this->sdk->send(testMessage());
    } catch (USmsRejectedException $e) {
        expect($e->responseCode())->toBeNull()
            ->and($e->statusId())->toBe(77);

        return;
    }

    $this->fail('Expected a USmsRejectedException.');
});

it('throws USmsRateLimitedException on a 429', function (): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make(
            ['statusID' => 429, 'message' => 'Too many requests', 'data' => null],
            429,
            ['Retry-After' => '20'],
        ),
    ]));

    try {
        $this->sdk->send(testMessage());
    } catch (USmsRateLimitedException $e) {
        expect($e->retryAfter())->toBe(20);

        return;
    }

    $this->fail('Expected a USmsRateLimitedException.');
});

it('throws the base USmsException on a bad api key', function (): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make(
            ['statusID' => 401, 'message' => 'Invalid API key or permission denied', 'data' => null],
            401,
        ),
    ]));

    try {
        $this->sdk->send(testMessage());
    } catch (USmsException $e) {
        expect($e::class)->toBe(USmsException::class)
            ->and($e->getCode())->toBe(401)
            ->and($e->getMessage())->toBe('Invalid API key or permission denied');

        return;
    }

    $this->fail('Expected a USmsException.');
});

it('throws the base USmsException on a gateway error that is not JSON', function (): void {
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make('<html><body>524: A timeout occurred</body></html>', 524),
    ]));

    try {
        $this->sdk->send(testMessage());
    } catch (USmsException $e) {
        expect($e::class)->toBe(USmsException::class)
            ->and($e->getCode())->toBe(524)
            ->and($e->getMessage())->toBe('uBill responded with HTTP 524');

        return;
    }

    $this->fail('Expected a USmsException.');
});

it('returns null and sends nothing while sending is disabled', function (): void {
    config()->set('usms.is_sending_disabled', true);

    $mock = new MockClient([SendSmsRequest::class => MockResponse::make(['statusID' => 0, 'smsID' => 1])]);
    $this->connector->withMockClient($mock);

    expect($this->sdk->send(testMessage()))->toBeNull();

    $mock->assertNothingSent();
});
