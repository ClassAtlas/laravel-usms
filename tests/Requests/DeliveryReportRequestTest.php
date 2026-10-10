<?php

use ClassAtlas\USms\DataObjects\Responses\DeliveryReport\ReportData;
use ClassAtlas\USms\DataObjects\Responses\DeliveryReport\ReportItemData;
use ClassAtlas\USms\Enums\DeliveryReportCode;
use ClassAtlas\USms\Enums\DeliveryStatus;
use ClassAtlas\USms\Exceptions\USmsRateLimitedException;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\Requests\DeliveryReportRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves GET /v1/sms/report/{smsID}', function (): void {
    $request = new DeliveryReportRequest(12345678);

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/report/12345678');
});

it('hydrates a ReportData DTO from the recorded fixture', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => scrubbedFixture('delivery-report'),
    ]));

    $report = $this->sdk->deliveryReport(12345678);

    expect($report)->toBeInstanceOf(ReportData::class)
        ->and($report->result)->not->toBeEmpty()
        ->and($report->result->first())->toBeInstanceOf(ReportItemData::class)
        ->and($report->result->first()->status())->toBeInstanceOf(DeliveryStatus::class);
});

it('reports a scheduled message as SENT, and not final, before its send time', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => scrubbedFixture('delivery-report-scheduled'),
    ]));

    $item = $this->sdk->deliveryReport(12345678)->result->first();

    expect($item->status())->toBe(DeliveryStatus::SENT)
        ->and($item->isFinal())->toBeFalse();
});

it('reads a status uBill sends as a string, and serialises it as the host stores it', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => MockResponse::make([
            'statusID' => 0,
            'result' => [['number' => '995555000000', 'statusID' => '1']],
        ]),
    ]));

    $item = $this->sdk->deliveryReport(1)->result->first();

    expect($item->status())->toBe(DeliveryStatus::RECEIVED)
        ->and($item->isFinal())->toBeTrue()
        ->and(json_encode($item))->toBe('{"number":"995555000000","statusID":1}');
});

it('keeps a status uBill does not document, as not final', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => MockResponse::make([
            'statusID' => 0,
            'result' => [['number' => '995555000000', 'statusID' => 9]],
        ]),
    ]));

    $item = $this->sdk->deliveryReport(1)->result->first();

    expect($item->statusID)->toBe(9)
        ->and($item->status())->toBeNull()
        ->and($item->isFinal())->toBeFalse();
});

it('builds an empty result when the response has none', function (array $body): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => MockResponse::make($body),
    ]));

    expect($this->sdk->deliveryReport(1)->result)->toBeEmpty();
})->with([
    'no result key' => [['statusID' => 0]],
    'empty result' => [['statusID' => 0, 'result' => []]],
]);

it('throws USmsRejectedException for an smsID uBill does not know', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => MockResponse::make([
            'statusID' => 12,
            'smsID' => null,
            'message' => 'SMS not found',
            'result' => [],
        ]),
    ]));

    try {
        $this->sdk->deliveryReport(999999999999);
    } catch (USmsRejectedException $e) {
        expect($e->responseCode())->toBe(DeliveryReportCode::SMS_NOT_FOUND);

        return;
    }

    $this->fail('Expected a USmsRejectedException.');
});

it('throws USmsRateLimitedException on a 429 instead of failing to cast it', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeliveryReportRequest::class => MockResponse::make(['statusID' => 429, 'message' => 'Too many requests'], 429),
    ]));

    expect(fn () => $this->sdk->deliveryReport(1))->toThrow(USmsRateLimitedException::class);
});
