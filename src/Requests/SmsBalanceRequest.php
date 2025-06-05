<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\Responses\SmsBalanceData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class SmsBalanceRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/sms/balance';
    }

    public function createDtoFromResponse(Response $response): SmsBalanceData
    {
        return SmsBalanceData::from($response->json());
    }
}
