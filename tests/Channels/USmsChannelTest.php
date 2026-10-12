<?php

use ClassAtlas\USms\Channels\USmsChannel;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\Enums\SendSmsCode;
use ClassAtlas\USms\Events\USmsSendFailed;
use ClassAtlas\USms\Events\USmsWasSent;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRateLimitedException;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\Tests\Support\TestSmsNotification;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function channelAnswering(MockResponse $response): array
{
    $mock = new MockClient([SendSmsRequest::class => $response]);

    $connector = new USmsConnector;
    $connector->withMockClient($mock);

    return [new USmsChannel(new USms($connector)), $mock];
}

function acceptedSend(): MockResponse
{
    return MockResponse::make(['statusID' => 0, 'smsID' => 111, 'message' => 'SMS Sended']);
}

it('announces an accepted send with its smsID, type and part count', function (): void {
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel] = channelAnswering(acceptedSend());

    $channel->send(new AnonymousNotifiable, new TestSmsNotification(text: str_repeat('ა', 71)));

    Event::assertDispatched(USmsWasSent::class, function (USmsWasSent $event): bool {
        $data = $event->USmsWasSentData;

        return $data->sendSmsData->smsID === 111
            && $data->notificationId === 'notif-1'
            && $data->notificationType === TestSmsNotification::class
            && $data->parts === 2;
    });
    Event::assertNotDispatched(USmsSendFailed::class);
});

it('announces a refused send as failed and does not throw', function (): void {
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel] = channelAnswering(MockResponse::make([
        'statusID' => 50,
        'smsID' => null,
        'message' => 'Valid numbers not found',
    ]));

    $channel->send(new AnonymousNotifiable, new TestSmsNotification);

    Event::assertDispatched(USmsSendFailed::class, function (USmsSendFailed $event): bool {
        $data = $event->USmsSendFailedData;

        return $data->code === SendSmsCode::NO_VALID_NUMBERS
            && $data->statusID === 50
            && $data->message === 'Valid numbers not found'
            && $data->notificationId === 'notif-1'
            && $data->notificationType === TestSmsNotification::class;
    });
    Event::assertNotDispatched(USmsWasSent::class);
});

it('throws a rate limit so the queue retries the send', function (): void {
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel] = channelAnswering(MockResponse::make(['statusID' => 429, 'message' => 'Too many requests'], 429));

    expect(fn () => $channel->send(new AnonymousNotifiable, new TestSmsNotification))
        ->toThrow(USmsRateLimitedException::class);

    Event::assertNotDispatched(USmsWasSent::class);
    Event::assertNotDispatched(USmsSendFailed::class);
});

it('throws a gateway error so the queue retries the send', function (): void {
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel] = channelAnswering(MockResponse::make('<html>502 Bad Gateway</html>', 502));

    expect(fn () => $channel->send(new AnonymousNotifiable, new TestSmsNotification))
        ->toThrow(USmsException::class);

    Event::assertNotDispatched(USmsSendFailed::class);
});

it('reports a listener failure after an accepted send instead of throwing, so the SMS is never sent twice', function (): void {
    Exceptions::fake();

    Event::listen(USmsWasSent::class, function (): void {
        throw new RuntimeException('listener blew up');
    });

    [$channel, $mock] = channelAnswering(acceptedSend());

    $channel->send(new AnonymousNotifiable, new TestSmsNotification);

    $mock->assertSentCount(1);
    Exceptions::assertReported(fn (RuntimeException $e): bool => $e->getMessage() === 'listener blew up');
});

it('announces a send uBill accepted without an smsID instead of failing the job', function (): void {
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel, $mock] = channelAnswering(MockResponse::make(['statusID' => 0, 'smsID' => null, 'message' => 'SMS Sent']));

    $channel->send(new AnonymousNotifiable, new TestSmsNotification);

    $mock->assertSentCount(1);
    Event::assertDispatched(USmsWasSent::class, fn (USmsWasSent $event): bool => $event->USmsWasSentData->sendSmsData instanceof SendSmsData
        && $event->USmsWasSentData->sendSmsData->smsID === null);
});

it('reports a failure while preparing the event after an accepted send instead of throwing', function (): void {
    Exceptions::fake();

    $usms = Mockery::mock(USms::class);
    $usms->shouldReceive('send')->once()->andReturn(new SendSmsData(111, 'SMS Sent'));
    $usms->shouldReceive('countSMSCharacters')->andThrow(new RuntimeException('counting blew up'));

    (new USmsChannel($usms))->send(new AnonymousNotifiable, new TestSmsNotification);

    Exceptions::assertReported(fn (RuntimeException $e): bool => $e->getMessage() === 'counting blew up');
});

it('still announces a send while sending is disabled, with no send data', function (): void {
    config()->set('usms.is_sending_disabled', true);
    Event::fake([USmsWasSent::class]);

    [$channel, $mock] = channelAnswering(acceptedSend());

    $channel->send(new AnonymousNotifiable, new TestSmsNotification);

    $mock->assertNothingSent();
    Event::assertDispatched(USmsWasSent::class, fn (USmsWasSent $event): bool => $event->USmsWasSentData->sendSmsData === null
        && $event->USmsWasSentData->parts === 1);
});

it('lets a listener failure through while sending is disabled, as nothing was sent', function (): void {
    config()->set('usms.is_sending_disabled', true);

    Event::listen(USmsWasSent::class, function (): void {
        throw new RuntimeException('listener blew up');
    });

    [$channel] = channelAnswering(acceptedSend());

    expect(fn () => $channel->send(new AnonymousNotifiable, new TestSmsNotification))
        ->toThrow(RuntimeException::class, 'listener blew up');
});

it('skips the send when the notification has no brand', function (): void {
    Exceptions::fake();
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel, $mock] = channelAnswering(acceptedSend());

    $channel->send(new AnonymousNotifiable, new TestSmsNotification(brandId: 0));

    $mock->assertNothingSent();
    Event::assertNotDispatched(USmsWasSent::class);
    Exceptions::assertReported(RuntimeException::class);
});

it('skips the send, and announces nothing, when the notification has nobody to go to', function (): void {
    Exceptions::fake();
    Event::fake([USmsWasSent::class, USmsSendFailed::class]);

    [$channel, $mock] = channelAnswering(acceptedSend());

    $channel->send(new AnonymousNotifiable, new TestSmsNotification(numbers: []));

    $mock->assertNothingSent();
    Event::assertNotDispatched(USmsWasSent::class);
    Event::assertNotDispatched(USmsSendFailed::class);
    Exceptions::assertNothingReported();
});

it('is resolved by the container with the bound USms', function (): void {
    expect(app(USmsChannel::class))->toBeInstanceOf(USmsChannel::class)
        ->and(app(USms::class))->toBe(app(USms::class));
});
