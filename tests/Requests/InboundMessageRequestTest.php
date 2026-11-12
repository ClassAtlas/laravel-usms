<?php

use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMessageData;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Requests\InboundMessageRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves GET /v1/sms/inbound/{eventID}', function (): void {
    $request = new InboundMessageRequest('29a24670-e631-4bf3-b593-ea35df838115');

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/inbound/29a24670-e631-4bf3-b593-ea35df838115')
        ->and((new InboundMessageRequest('a/b'))->resolveEndpoint())->toBe('/v1/sms/inbound/a%2Fb');
});

it('hydrates the incoming message', function (): void {
    $this->connector->withMockClient(new MockClient([
        InboundMessageRequest::class => scrubbedFixture('inbound-sms-message'),
    ]));

    $message = $this->sdk->inboundMessage('29a24670-e631-4bf3-b593-ea35df838115');

    expect($message)->toBeInstanceOf(InboundMessageData::class)
        ->and($message->eventID)->toBe('29a24670-e631-4bf3-b593-ea35df838115')
        ->and($message->text)->toBe('Example incoming SMS');
});

it('throws uBill\'s 404 for a message it does not know', function (): void {
    $this->connector->withMockClient(new MockClient([
        InboundMessageRequest::class => MockResponse::make(['statusID' => 404, 'message' => 'Message not found', 'result' => []], 404),
    ]));

    expect(fn () => $this->sdk->inboundMessage('29a24670-e631-4bf3-b593-ea35df838115'))
        ->toThrow(USmsException::class, 'Message not found');
});

it('refuses a success answer without the message', function (): void {
    $this->connector->withMockClient(new MockClient([
        InboundMessageRequest::class => MockResponse::make(['statusID' => 0, 'result' => []]),
    ]));

    expect(fn () => $this->sdk->inboundMessage('29a24670-e631-4bf3-b593-ea35df838115'))
        ->toThrow(UnexpectedValueException::class);
});
