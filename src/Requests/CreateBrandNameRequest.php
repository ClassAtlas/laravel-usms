<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\Contracts\ResponseCode;
use ClassAtlas\USms\DataObjects\Responses\BrandNameCreateData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\Enums\BrandNameCreateCode;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CreateBrandNameRequest extends Request implements HasBody
{
    use HasJsonBody, TransformsErrorResponse;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $brandName,
        protected string $description,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/brandNameCreate';
    }

    /**
     * @return array{brandName: string, description: string}
     */
    public function defaultBody(): array
    {
        return [
            'brandName' => $this->brandName,
            'description' => $this->description,
        ];
    }

    public function createDtoFromResponse(Response $response): BrandNameCreateData|ErrorData
    {
        return $this->errorFromResponse($response) ?? BrandNameCreateData::from($response->json());
    }

    protected function responseCode(int $statusId): ?ResponseCode
    {
        return BrandNameCreateCode::tryFrom($statusId);
    }
}
