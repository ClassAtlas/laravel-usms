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
use UnexpectedValueException;

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
            'result' => array_map(self::item(...), is_array($result) ? array_values($result) : []),
        ]);
    }

    /**
     * uBill's examples send `statusID` as a string, so both fields are read
     * as scalars and converted.
     *
     * @return array{number: string, statusID: int}
     */
    private static function item(mixed $item): array
    {
        if (! is_array($item) || ! is_scalar($item['number'] ?? null) || ! is_numeric($item['statusID'] ?? null)) {
            throw new UnexpectedValueException('uBill sent a delivery report entry without a readable number and statusID.');
        }

        return ['number' => (string) $item['number'], 'statusID' => (int) $item['statusID']];
    }

    protected function responseCode(int $statusId): ?ResponseCode
    {
        return DeliveryReportCode::tryFrom($statusId);
    }
}
