<?php

use ClassAtlas\USms\DataObjects\Responses\BrandNameCreateData;
use ClassAtlas\USms\Enums\BrandNameCreateCode;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\Requests\CreateBrandNameRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves POST /v1/sms/brandNameCreate with the brand name', function (): void {
    $request = new CreateBrandNameRequest('MyBrand');

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/brandNameCreate')
        ->and($request->body()->all())->toBe(['brandName' => 'MyBrand']);
});

it('hydrates a BrandNameCreateData DTO from the fixture', function (): void {
    $this->connector->withMockClient(new MockClient([
        CreateBrandNameRequest::class => scrubbedFixture('create-brand-name'),
    ]));

    $created = $this->sdk->createBrandName('MyBrand');

    expect($created)->toBeInstanceOf(BrandNameCreateData::class)
        ->and($created->brandID)->toBe(123);
});

it('reads a refusal with the brand-create meaning of the code, not the send one', function (int $statusId, BrandNameCreateCode $code): void {
    $this->connector->withMockClient(new MockClient([
        CreateBrandNameRequest::class => MockResponse::make(['statusID' => $statusId, 'message' => 'refused']),
    ]));

    try {
        $this->sdk->createBrandName('MyBrand');
    } catch (USmsRejectedException $e) {
        expect($e->responseCode())->toBe($code);

        return;
    }

    $this->fail('Expected a USmsRejectedException.');
})->with([
    'empty name' => [10, BrandNameCreateCode::EMPTY_NAME],
    'invalid length' => [20, BrandNameCreateCode::INVALID_LENGTH],
    'forbidden characters' => [30, BrandNameCreateCode::FORBIDDEN_CHARACTERS],
    'already exists' => [40, BrandNameCreateCode::ALREADY_EXISTS],
    'previous still pending' => [50, BrandNameCreateCode::PREVIOUS_PENDING],
]);
