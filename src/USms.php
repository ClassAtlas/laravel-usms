<?php

namespace ClassAtlas\USms;

use ClassAtlas\USms\DataObjects\BrandNameCreateData;
use ClassAtlas\USms\DataObjects\BrandNames\BrandNameData;
use ClassAtlas\USms\DataObjects\DeliveryReport\ReportData;
use ClassAtlas\USms\DataObjects\SendSmsData;
use ClassAtlas\USms\DataObjects\SmsBalanceData;
use ClassAtlas\USms\Requests\CreateBrandNameRequest;
use ClassAtlas\USms\Requests\DeliveryReportRequest;
use ClassAtlas\USms\Requests\ListBrandNameRequest;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\Requests\SmsBalanceRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class USms
{
    protected USmsConnector $connector;

    public function __construct()
    {
        $this->connector = new USmsConnector;
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function createBrandName(string $brandName): BrandNameCreateData
    {
        return $this->connector->send(new CreateBrandNameRequest($brandName))->dto();
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function listBrandName(): BrandNameData
    {
        return $this->connector->send(new ListBrandNameRequest)->dto();
    }

    /**
     * @param  string[]  $numbers
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendSms(int $brandId, array $numbers, string $text, bool $stopList): ?SendSmsData
    {
        $request = new SendSmsRequest($brandId, $numbers, $text, $stopList);

        return config('usms.is_sending_disabled')
            ? null
            : $this->connector->send($request)->dto();
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function balance(): SmsBalanceData
    {
        return $this->connector->send(new SmsBalanceRequest)->dto();
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function deliveryReport(int $smsId): ReportData
    {
        return $this->connector->send(new DeliveryReportRequest($smsId))->dto();
    }
}
