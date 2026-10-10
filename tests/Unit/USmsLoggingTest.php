<?php

use ClassAtlas\USms\DataObjects\MessageData;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/*
 * This test locks in the "no secrets in logs" contract of
 * USmsConnector::boot(). If anyone ever extends the logging middleware to
 * include headers or request/response bodies, this test fails loudly.
 */

beforeEach(function (): void {
    config()->set('usms.logging_enabled', true);
    config()->set('usms.log_channel', null);
    config()->set('usms.api_key', 'super-secret-api-key');

    $this->buffer = new ArrayObject;

    Log::listen(function ($event): void {
        $this->buffer[] = [
            'level' => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];
    });

    $this->connector = new USmsConnector;
    $this->connector->withMockClient(new MockClient([
        SendSmsRequest::class => MockResponse::make(['statusID' => 0, 'smsID' => 111, 'message' => 'SMS Sended']),
    ]));

    (new USms($this->connector))->send(new MessageData(29, ['995555123456'], 'Your code is 4821'));
});

it('never writes the api key, the recipient or the text to logs', function (): void {
    $flattened = json_encode(iterator_to_array($this->buffer));

    expect($flattened)
        ->not->toContain('super-secret-api-key')
        ->not->toContain('995555123456')
        ->not->toContain('4821');
});

it('still logs method, url, and status when logging is enabled', function (): void {
    $records = iterator_to_array($this->buffer);

    expect($records)->toHaveCount(2)
        ->and($records[0]['message'])->toBe('[USms] request')
        ->and($records[0]['context'])->toMatchArray(['method' => 'POST'])
        ->and($records[0]['context']['url'])->toContain('/v1/sms/send')
        ->and($records[1]['message'])->toBe('[USms] response')
        ->and($records[1]['context'])->toMatchArray(['status' => 200]);
});
