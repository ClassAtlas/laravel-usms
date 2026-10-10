<?php

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\PersonalizedMessageData;
use ClassAtlas\USms\DataObjects\RecipientData;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\Requests\DeliveryReportRequest;
use ClassAtlas\USms\Requests\ListBrandNameRequest;
use ClassAtlas\USms\Requests\SendPersonalizedSmsRequest;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\Requests\SmsBalanceRequest;
use ClassAtlas\USms\Tests\Support\RecordingMode;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Http\Faking\MockClient;

/*
 * Live recording sweep.
 *
 * uBill has no sandbox, so recording talks to the real account and every send
 * scenario costs real SMS, delivered to UBILL_SAFE_SENDER from the package's
 * .env. All tests skip unless USMS_FIXTURE_RECORDING=1 is set, and a scenario
 * whose fixture already exists is skipped too — delete the fixture to record
 * it again. Run one scenario at a time:
 *
 *   USMS_FIXTURE_RECORDING=1 vendor/bin/pest --filter='records a plain send'
 *
 * Brand create and delete are never recorded: they would change the account.
 */

function liveSdk(string $requestClass, string $fixture): USms
{
    $connector = new USmsConnector;
    $connector->withMockClient(new MockClient([$requestClass => scrubbedFixture($fixture)]));

    return new USms($connector);
}

function recordingScenario(object $test, string ...$fixtures): void
{
    if (! RecordingMode::isRecording()) {
        $test->markTestSkipped('Recording mode only (set USMS_FIXTURE_RECORDING=1).');
    }

    foreach ($fixtures as $fixture) {
        if (file_exists(__DIR__.'/../Fixtures/Saloon/'.$fixture.'.json')) {
            $test->markTestSkipped("Fixture [{$fixture}] exists; delete it to record again.");
        }
    }
}

function announce(string $line): void
{
    fwrite(STDOUT, "\n  ▸ {$line}\n");
}

it('records the balance', function (): void {
    recordingScenario($this, 'sms-balance');

    $balance = liveSdk(SmsBalanceRequest::class, 'sms-balance')->balance();

    announce("balance: {$balance->sms}");

    expect($balance->sms)->toBeGreaterThanOrEqual(0);
})->group('recording-sweep');

it('records the brand names', function (): void {
    recordingScenario($this, 'brand-names');

    $brands = liveSdk(ListBrandNameRequest::class, 'brand-names')->listBrandName();

    announce('brands: '.count($brands->brands));

    expect($brands->brands)->not->toBeEmpty();
})->group('recording-sweep');

it('records a plain send and its delivery report', function (): void {
    recordingScenario($this, 'send-sms', 'delivery-report');

    $sent = liveSdk(SendSmsRequest::class, 'send-sms')->send(testMessage('ClassAtlas test: plain'));

    announce("plain send smsID: {$sent->smsID} ({$sent->message})");

    $report = liveSdk(DeliveryReportRequest::class, 'delivery-report')->deliveryReport($sent->smsID);

    announce('report: '.json_encode($report->result));

    expect($sent->smsID)->toBeGreaterThan(0);
})->group('recording-sweep');

it('records an otp send', function (): void {
    recordingScenario($this, 'send-sms-otp');

    $sent = liveSdk(SendSmsRequest::class, 'send-sms-otp')->send(testMessage('ClassAtlas test: otp', otp: true));

    announce("otp send smsID: {$sent->smsID} ({$sent->message})");

    expect($sent->smsID)->toBeGreaterThan(0);
})->group('recording-sweep');

it('records a send with a callback url', function (): void {
    recordingScenario($this, 'send-sms-callback-url');

    $sent = liveSdk(SendSmsRequest::class, 'send-sms-callback-url')->send(
        testMessage('ClassAtlas test: callback', callbackUrl: 'https://example.test/usms/callback'),
    );

    announce("callbackUrl send smsID: {$sent->smsID} ({$sent->message})");

    expect($sent->smsID)->toBeGreaterThan(0);
})->group('recording-sweep');

it('records a scheduled send', function (): void {
    recordingScenario($this, 'send-sms-scheduled', 'delivery-report-scheduled');

    $sendAt = CarbonImmutable::now('Asia/Tbilisi')->addMinutes(4);

    $sent = liveSdk(SendSmsRequest::class, 'send-sms-scheduled')->send(
        testMessage('ClassAtlas test: scheduled '.$sendAt->format('H:i'), sendAt: $sendAt),
    );

    announce("scheduled for {$sendAt->format('Y-m-d H:i')} Tbilisi, smsID: {$sent->smsID} ({$sent->message})");

    $report = liveSdk(DeliveryReportRequest::class, 'delivery-report-scheduled')->deliveryReport($sent->smsID);

    announce('report before the send time: '.json_encode($report->result));

    expect($sent->smsID)->toBeGreaterThan(0);
})->group('recording-sweep');

it('records what a send time in the past gets', function (): void {
    recordingScenario($this, 'send-sms-past-send-time');

    try {
        $sent = liveSdk(SendSmsRequest::class, 'send-sms-past-send-time')->send(testMessage(
            'ClassAtlas test: past',
            sendAt: CarbonImmutable::now('Asia/Tbilisi')->subDay(),
        ));

        announce("past sendTime was ACCEPTED, smsID: {$sent->smsID} ({$sent->message})");
    } catch (USmsRejectedException $e) {
        announce("past sendTime refused: {$e->statusId()} {$e->getMessage()}");
    }

    expect(true)->toBeTrue();
})->group('recording-sweep');

it('records a personalized send', function (): void {
    recordingScenario($this, 'send-personalized-sms');

    $sent = liveSdk(SendPersonalizedSmsRequest::class, 'send-personalized-sms')->sendPersonalized(
        new PersonalizedMessageData(RecordingMode::brandId(), [
            new RecipientData(RecordingMode::number(), 'ClassAtlas test: personalized A'),
            new RecipientData(RecordingMode::number(), 'ClassAtlas test: personalized B'),
        ]),
    );

    announce("personalized send smsID: {$sent->smsID} ({$sent->message})");

    expect($sent->smsID)->toBeGreaterThan(0);
})->group('recording-sweep');

it('records a personalized send with options', function (): void {
    recordingScenario($this, 'send-personalized-sms-options');

    $sent = liveSdk(SendPersonalizedSmsRequest::class, 'send-personalized-sms-options')->sendPersonalized(
        new PersonalizedMessageData(
            brandId: RecordingMode::brandId(),
            recipients: [new RecipientData(RecordingMode::number(), 'ClassAtlas test: personalized otp')],
            otp: true,
            callbackUrl: 'https://example.test/usms/callback',
        ),
    );

    announce("personalized send with options smsID: {$sent->smsID} ({$sent->message})");

    expect($sent->smsID)->toBeGreaterThan(0);
})->group('recording-sweep');

it('reads a delivery report again', function (): void {
    if (! RecordingMode::isRecording() || ! getenv('USMS_REPORT_SMS_ID')) {
        $this->markTestSkipped('Recording mode with USMS_REPORT_SMS_ID only.');
    }

    $report = (new USms(new USmsConnector))->deliveryReport((int) getenv('USMS_REPORT_SMS_ID'));

    announce('report: '.json_encode($report->result));

    expect($report->result)->not->toBeNull();
})->group('recording-sweep');
