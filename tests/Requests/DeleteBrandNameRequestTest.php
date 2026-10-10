<?php

use ClassAtlas\USms\DataObjects\Responses\BrandNameDeleteData;
use ClassAtlas\USms\Enums\BrandNameDeleteCode;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\Requests\DeleteBrandNameRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves POST /v1/sms/brandNameDelete with the brand id', function (): void {
    $request = new DeleteBrandNameRequest(123);

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/brandNameDelete')
        ->and($request->body()->all())->toBe(['brandID' => 123]);
});

it('hydrates a BrandNameDeleteData DTO from the fixture', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeleteBrandNameRequest::class => scrubbedFixture('delete-brand-name'),
    ]));

    expect($this->sdk->deleteBrandName(123))->toBeInstanceOf(BrandNameDeleteData::class);
});

it('tells a brand uBill does not have apart from any other refusal', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeleteBrandNameRequest::class => MockResponse::make(['statusID' => 10, 'message' => 'BrandName not found']),
    ]));

    try {
        $this->sdk->deleteBrandName(123);
    } catch (USmsRejectedException $e) {
        expect($e->responseCode())->toBe(BrandNameDeleteCode::NOT_FOUND);

        return;
    }

    $this->fail('Expected a USmsRejectedException.');
});

it('throws USmsException on a server error', function (): void {
    $this->connector->withMockClient(new MockClient([
        DeleteBrandNameRequest::class => MockResponse::make(['message' => 'Server error'], 500),
    ]));

    expect(fn () => $this->sdk->deleteBrandName(123))->toThrow(USmsException::class, 'Server error');
});
