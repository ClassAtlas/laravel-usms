<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\Contracts\ResponseCode;
use ClassAtlas\USms\DataObjects\Responses\BrandNameDeleteData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\Enums\BrandNameDeleteCode;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class DeleteBrandNameRequest extends Request implements HasBody
{
    use HasJsonBody, TransformsErrorResponse;

    protected Method $method = Method::POST;

    public function __construct(protected int $brandId) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/brandNameDelete';
    }

    /**
     * @return array{brandID: int}
     */
    public function defaultBody(): array
    {
        return [
            'brandID' => $this->brandId,
        ];
    }

    public function createDtoFromResponse(Response $response): BrandNameDeleteData|ErrorData
    {
        return $this->errorFromResponse($response) ?? BrandNameDeleteData::from($response->json());
    }

    protected function responseCode(int $statusId): ?ResponseCode
    {
        return BrandNameDeleteCode::tryFrom($statusId);
    }
}
