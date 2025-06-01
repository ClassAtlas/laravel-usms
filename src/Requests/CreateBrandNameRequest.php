<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\BrandNameCreateData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CreateBrandNameRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $brandName) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/brandNameCreate';
    }

    /**
     * @return array{brandName: string}
     */
    public function defaultBody(): array
    {
        return [
            'brandName' => $this->brandName,
        ];
    }

    public function createDtoFromResponse(Response $response): BrandNameCreateData
    {
        return BrandNameCreateData::from($response->json());
    }
}
