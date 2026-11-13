<?php

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\InboundFilterData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMessageData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundPageData;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRateLimitedException;
use ClassAtlas\USms\Requests\InboundSmsRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

/**
 * @param  list<array<string, mixed>>  $messages
 * @return array<string, mixed>
 */
function inboundPage(array $messages, bool $hasMore, ?string $nextCursor): array
{
    return [
        'statusID' => 0,
        'meta' => [
            'from' => '2026-11-01T09:00:00.000000Z',
            'to' => '2026-11-02T09:00:00.000000Z',
            'limit' => 2,
            'count' => count($messages),
            'hasMore' => $hasMore,
            'nextCursor' => $nextCursor,
        ],
        'result' => $messages,
    ];
}

/**
 * @return array<string, mixed>
 */
function inboundMessage(string $eventId, string $text): array
{
    return ['eventID' => $eventId, 'from' => '555000000', 'to' => '90566', 'text' => $text, 'noSms' => false, 'sendAt' => '2026-11-02T08:30:00.396288Z'];
}

it('resolves GET /v1/sms/inbound with no query when nothing is filtered', function (): void {
    $request = new InboundSmsRequest;

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/inbound')
        ->and($request->query()->all())->toBe([]);
});

it('sends a date as the day it names, whatever the app\'s timezone', function (): void {
    $request = new InboundSmsRequest(new InboundFilterData(date: new CarbonImmutable('2026-11-13 00:00', 'Asia/Tokyo')));

    expect($request->query()->all())->toBe(['date' => '2026-11-13']);
});

it('sends a window as timestamps with their offset, with the limit and cursor', function (): void {
    $request = new InboundSmsRequest(new InboundFilterData(
        from: new CarbonImmutable('2026-11-02 00:00:00', 'Asia/Tbilisi'),
        to: new CarbonImmutable('2026-11-03 00:00:00', 'Asia/Tbilisi'),
        limit: 100,
        cursor: '29a24670-e631-4bf3-b593-ea35df838115',
    ));

    expect($request->query()->all())->toBe([
        'from' => '2026-11-02T00:00:00.000000+04:00',
        'to' => '2026-11-03T00:00:00.000000+04:00',
        'limit' => 100,
        'cursor' => '29a24670-e631-4bf3-b593-ea35df838115',
    ]);
});

it('refuses a filter uBill would refuse', function (Closure $filter): void {
    expect($filter)->toThrow(InvalidArgumentException::class);
})->with([
    'a date with a window' => [fn () => new InboundFilterData(date: CarbonImmutable::now(), from: CarbonImmutable::now())],
    'a reversed window' => [fn () => new InboundFilterData(from: CarbonImmutable::now(), to: CarbonImmutable::now()->subHour())],
    'no messages per page' => [fn () => new InboundFilterData(limit: 0)],
    'more than 100 per page' => [fn () => new InboundFilterData(limit: 101)],
]);

it('hydrates a page of incoming messages', function (): void {
    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => scrubbedFixture('inbound-sms'),
    ]));

    $page = $this->sdk->inbound();

    expect($page)->toBeInstanceOf(InboundPageData::class)
        ->and($page->meta->from->toIso8601ZuluString())->toBe('2026-11-01T09:00:00Z')
        ->and($page->meta->to->toIso8601ZuluString())->toBe('2026-11-02T09:00:00Z')
        ->and($page->meta->limit)->toBe(50)
        ->and($page->meta->count)->toBe(1)
        ->and($page->meta->hasMore)->toBeFalse()
        ->and($page->meta->nextCursor)->toBe('29a24670-e631-4bf3-b593-ea35df838115')
        ->and($page->result)->toHaveCount(1);

    $message = $page->result->first();

    expect($message)->toBeInstanceOf(InboundMessageData::class)
        ->and($message->eventID)->toBe('29a24670-e631-4bf3-b593-ea35df838115')
        ->and($message->from)->toBe('555000000')
        ->and($message->to)->toBe('90566')
        ->and($message->text)->toBe('Example incoming SMS')
        ->and($message->noSms)->toBeFalse()
        ->and($message->sendAt->format('Y-m-d\TH:i:s.uP'))->toBe('2026-11-02T08:30:00.396288+00:00');
});

it('hydrates an empty page', function (): void {
    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => scrubbedFixture('inbound-sms-empty'),
    ]));

    $page = $this->sdk->inbound();

    expect($page->result)->toBeEmpty()
        ->and($page->meta->nextCursor)->toBeNull();
});

it('reads a message without a send time, as uBill has for older ones', function (): void {
    $message = inboundMessage('a', 'Old');
    $message['sendAt'] = null;

    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => MockResponse::make(inboundPage([$message], false, null)),
    ]));

    expect($this->sdk->inbound()->result->first()->sendAt)->toBeNull();
});

it('refuses a message without a readable field', function (): void {
    $message = inboundMessage('a', 'Hi');
    unset($message['from']);

    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => MockResponse::make(inboundPage([$message], false, null)),
    ]));

    expect(fn () => $this->sdk->inbound())->toThrow(UnexpectedValueException::class, '[from]');
});

it('throws what uBill refuses with its HTTP status', function (int $status, string $message): void {
    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => MockResponse::make(['statusID' => $status, 'message' => $message, 'result' => []], $status),
    ]));

    try {
        $this->sdk->inbound();
    } catch (USmsException $e) {
        expect($e)->not->toBeInstanceOf(USmsRateLimitedException::class)
            ->and($e->getCode())->toBe($status)
            ->and($e->getMessage())->toBe($message);

        return;
    }

    $this->fail('Expected a USmsException.');
})->with([
    'invalid date' => [400, 'Invalid date'],
    'no permission' => [401, 'Invalid API key or permission denied'],
    'IP not allowed' => [403, 'IP not allowed'],
    'unknown cursor' => [404, 'Cursor not found'],
]);

it('throws USmsRateLimitedException on a 429', function (): void {
    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => MockResponse::make(['statusID' => 429, 'message' => 'Too many requests', 'result' => []], 429),
    ]));

    expect(fn () => $this->sdk->inbound())->toThrow(USmsRateLimitedException::class);
});

it('reads every page, keeping the first page\'s window', function (): void {
    $mock = new MockClient([
        MockResponse::make(inboundPage([inboundMessage('a', 'One'), inboundMessage('b', 'Two')], true, 'cursor-1')),
        MockResponse::make(inboundPage([inboundMessage('c', 'Three')], false, 'cursor-2')),
    ]);
    $this->connector->withMockClient($mock);

    $messages = $this->sdk->inboundAll(new InboundFilterData(date: new CarbonImmutable('2026-11-01', 'Asia/Tbilisi'), limit: 2))->all();

    expect(array_map(fn (InboundMessageData $message): string => $message->eventID, $messages))->toBe(['a', 'b', 'c'])
        ->and(array_keys($messages))->toBe([0, 1, 2]);

    $second = $mock->getRecordedResponses()[1]->getPendingRequest()->query()->all();

    expect($second)->toBe([
        'from' => '2026-11-01T09:00:00.000000+00:00',
        'to' => '2026-11-02T09:00:00.000000+00:00',
        'limit' => 2,
        'cursor' => 'cursor-1',
    ]);
});

it('stops reading when uBill hands back the same cursor', function (): void {
    $mock = new MockClient([
        MockResponse::make(inboundPage([inboundMessage('a', 'One')], true, 'cursor-1')),
        MockResponse::make(inboundPage([], true, 'cursor-1')),
    ]);
    $this->connector->withMockClient($mock);

    expect($this->sdk->inboundAll()->all())->toHaveCount(1);

    $mock->assertSentCount(2);
});

it('stops reading when uBill hands back a cursor it gave before', function (): void {
    $mock = new MockClient([
        MockResponse::make(inboundPage([inboundMessage('a', 'One')], true, 'cursor-1')),
        MockResponse::make(inboundPage([inboundMessage('b', 'Two')], true, 'cursor-2')),
        MockResponse::make(inboundPage([], true, 'cursor-1')),
    ]);
    $this->connector->withMockClient($mock);

    expect($this->sdk->inboundAll()->all())->toHaveCount(2);

    $mock->assertSentCount(3);
});

it('reads a message whose noSms uBill left out as not under the policy', function (): void {
    $message = inboundMessage('a', 'Old');
    unset($message['noSms']);

    $this->connector->withMockClient(new MockClient([
        InboundSmsRequest::class => MockResponse::make(inboundPage([$message], false, null)),
    ]));

    expect($this->sdk->inbound()->result->first()->noSms)->toBeFalse();
});
