<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\Responses\BrandNames\BrandNameData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class ListBrandNameRequest extends Request
{
    use TransformsErrorResponse;

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/sms/brandNames';
    }

    public function createDtoFromResponse(Response $response): BrandNameData|ErrorData
    {
        return $this->errorFromResponse($response) ?? BrandNameData::from($response->json());
    }
}
