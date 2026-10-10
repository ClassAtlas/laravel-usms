<?php

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\Responses\BrandNames\BrandNameData;
use ClassAtlas\USms\DataObjects\Responses\BrandNames\BrandNameItemData;
use ClassAtlas\USms\Enums\BrandNameAuthorized;
use ClassAtlas\USms\Requests\ListBrandNameRequest;
use ClassAtlas\USms\USms;
use ClassAtlas\USms\USmsConnector;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;

beforeEach(function (): void {
    $this->connector = new USmsConnector;
    $this->sdk = new USms($this->connector);
});

it('resolves GET /v1/sms/brandNames', function (): void {
    $request = new ListBrandNameRequest;

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/v1/sms/brandNames');
});

it('hydrates a BrandNameData DTO from the recorded fixture', function (): void {
    $this->connector->withMockClient(new MockClient([
        ListBrandNameRequest::class => scrubbedFixture('brand-names'),
    ]));

    $brands = $this->sdk->listBrandName();

    expect($brands)->toBeInstanceOf(BrandNameData::class)
        ->and($brands->brands)->not->toBeEmpty()
        ->and($brands->brands[0])->toBeInstanceOf(BrandNameItemData::class)
        ->and($brands->brands[0]->id)->toBeInt()
        ->and($brands->brands[0]->authorized)->toBeInstanceOf(BrandNameAuthorized::class)
        ->and($brands->brands[0]->createdAt)->toBeInstanceOf(CarbonImmutable::class);
});
