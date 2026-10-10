<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\DataObjects\Responses\SmsBalanceData;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class SmsBalanceRequest extends Request
{
    use TransformsErrorResponse;

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/sms/balance';
    }

    public function createDtoFromResponse(Response $response): SmsBalanceData|ErrorData
    {
        return $this->errorFromResponse($response) ?? SmsBalanceData::from($response->json());
    }
}
