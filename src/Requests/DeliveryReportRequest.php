<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\Contracts\ResponseCode;
use ClassAtlas\USms\DataObjects\Responses\DeliveryReport\ReportData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\Enums\DeliveryReportCode;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class DeliveryReportRequest extends Request
{
    use TransformsErrorResponse;

    protected Method $method = Method::GET;

    public function __construct(
        protected int $smsId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/report/'.$this->smsId;
    }

    public function createDtoFromResponse(Response $response): ReportData|ErrorData
    {
        if ($error = $this->errorFromResponse($response)) {
            return $error;
        }

        $result = $response->json('result');

        return ReportData::from([
            'result' => array_map(
                fn (array $item): array => ['number' => (string) $item['number'], 'statusID' => (int) $item['statusID']],
                is_array($result) ? $result : [],
            ),
        ]);
    }

    protected function responseCode(int $statusId): ?ResponseCode
    {
        return DeliveryReportCode::tryFrom($statusId);
    }
}
