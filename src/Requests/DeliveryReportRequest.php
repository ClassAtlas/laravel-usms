<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\Responses\DeliveryReport\ReportData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class DeliveryReportRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $smsId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/report/'.$this->smsId;
    }

    public function createDtoFromResponse(Response $response): ReportData
    {
        return ReportData::from($response->json());
    }
}
