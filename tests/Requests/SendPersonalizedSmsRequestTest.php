<?php

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\PersonalizedMessageData;
use ClassAtlas\USms\DataObjects\RecipientData;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\Enums\SendSmsCode;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\Requests\SendPersonalizedSmsRequest;
use ClassAtlas\USms\Tests\Support\RecordingMode;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function personalizedMessage(mixed ...$options): PersonalizedMessageData
{
    return new PersonalizedMessageData(
        RecordingMode::brandId(),
        [
            new RecipientData(RecordingMode::number(), 'ClassAtlas test A'),
            new RecipientData(RecordingMode::number(), 'ClassAtlas test B'),
        ],
        ...$options,
    );
}

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves POST /v1/sms/send', function (): void {
    $request = new SendPersonalizedSmsRequest(personalizedMessage());

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/send');
});

it('sends a text per number and no shared text', function (): void {
    $request = new SendPersonalizedSmsRequest(new PersonalizedMessageData(29, [
        new RecipientData('995555000001', 'Hello Nika'),
        new RecipientData('995555000002', 'Hello Giorgi'),
    ]));

    expect($request->body()->all())->toBe([
        'brandID' => 29,
        'numbers' => [
            ['number' => '995555000001', 'text' => 'Hello Nika'],
            ['number' => '995555000002', 'text' => 'Hello Giorgi'],
        ],
        'stopList' => false,
    ]);
});

it('adds the same options a plain send takes', function (): void {
    $request = new SendPersonalizedSmsRequest(new PersonalizedMessageData(
        brandId: 29,
        recipients: [new RecipientData('995555000001', 'Hello Nika')],
        otp: true,
        sendAt: CarbonImmutable::parse('2026-02-01 23:01:00', 'Asia/Tbilisi'),
        callbackUrl: 'https://example.test/usms/callback',
    ), 'Asia/Tbilisi');

    expect($request->body()->all())->toMatchArray([
        'otp' => true,
        'sendTime' => '2026-02-01 23:01',
        'callbackUrl' => 'https://example.test/usms/callback',
    ]);
});

it('hydrates one SendSmsData for the whole batch from the recorded fixture', function (string $fixture, array $options): void {
    $this->connector->withMockClient(new MockClient([
        SendPersonalizedSmsRequest::class => scrubbedFixture($fixture),
    ]));

    $response = $this->sdk->sendPersonalized(personalizedMessage(...$options));

    expect($response)->toBeInstanceOf(SendSmsData::class)
        ->and($response->smsID)->toBeGreaterThan(0);
})->with([
    'plain' => ['send-personalized-sms', []],
    'otp and callback url' => ['send-personalized-sms-options', ['otp' => true, 'callbackUrl' => 'https://example.test/usms/callback']],
]);

it('throws USmsRejectedException with the send code when uBill refuses', function (): void {
    $this->connector->withMockClient(new MockClient([
        SendPersonalizedSmsRequest::class => MockResponse::make(['statusID' => 40, 'smsID' => null, 'message' => 'Not enough SMS']),
    ]));

    try {
        $this->sdk->sendPersonalized(personalizedMessage());
    } catch (USmsRejectedException $e) {
        expect($e->responseCode())->toBe(SendSmsCode::INSUFFICIENT_BALANCE);

        return;
    }

    $this->fail('Expected a USmsRejectedException.');
});

it('returns null and sends nothing while sending is disabled', function (): void {
    config()->set('usms.is_sending_disabled', true);

    $mock = new MockClient([SendPersonalizedSmsRequest::class => MockResponse::make(['statusID' => 0, 'smsID' => 1])]);
    $this->connector->withMockClient($mock);

    expect($this->sdk->sendPersonalized(personalizedMessage()))->toBeNull();

    $mock->assertNothingSent();
});
