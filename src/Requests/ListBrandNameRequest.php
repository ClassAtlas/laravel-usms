<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\Responses\BrandNames\BrandNameData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class ListBrandNameRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/sms/brandNames';
    }

    public function createDtoFromResponse(Response $response): BrandNameData
    {
        return BrandNameData::from($response->json());
    }
}
