<?php

namespace ClassAtlas\USms;

use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\UnknownCallbackData;
use ClassAtlas\USms\DataObjects\MessageData;
use ClassAtlas\USms\DataObjects\PersonalizedMessageData;
use ClassAtlas\USms\DataObjects\Responses\BrandNameCreateData;
use ClassAtlas\USms\DataObjects\Responses\BrandNameDeleteData;
use ClassAtlas\USms\DataObjects\Responses\BrandNames\BrandNameData;
use ClassAtlas\USms\DataObjects\Responses\DeliveryReport\ReportData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\DataObjects\Responses\SmsBalanceData;
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Requests\CreateBrandNameRequest;
use ClassAtlas\USms\Requests\DeleteBrandNameRequest;
use ClassAtlas\USms\Requests\DeliveryReportRequest;
use ClassAtlas\USms\Requests\ListBrandNameRequest;
use ClassAtlas\USms\Requests\SendPersonalizedSmsRequest;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\Requests\SmsBalanceRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;

class USms
{
    public const int SINGLE_LIMIT = 160;

    public const int  MULTI_LIMIT = 153;

    public const int  SINGLE_UNICODE_LIMIT = 70;

    public const int  MULTI_UNICODE_LIMIT = 67;

    public const int  MAX_SMS_PARTS = 6;

    public const string PROVIDER_TIMEZONE = 'Asia/Tbilisi';

    protected USmsConnector $connector;

    protected CallbackHandler $callbackHandler;

    public function __construct(
        ?USmsConnector $connector = null,
        ?CallbackHandler $callbackHandler = null,
    ) {
        $this->connector = $connector ?? new USmsConnector;
        $this->callbackHandler = $callbackHandler ?? new CallbackHandler(
            verifier: new CallbackKeyVerifier(
                key: (string) config('usms.callback_key', ''),
            ),
            timezone: $this->timezone(),
        );
    }

    /**
     * Send one text to one or more numbers. Returns null when sending is
     * disabled (`usms.is_sending_disabled`).
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function send(MessageData $message): ?SendSmsData
    {
        if (config('usms.is_sending_disabled')) {
            return null;
        }

        /** @var SendSmsData $dto */
        $dto = $this->dispatch(new SendSmsRequest($message, $this->timezone()));

        return $dto;
    }

    /**
     * Send a different text to each number in a single request. uBill answers
     * the whole batch with one smsID. Returns null when sending is disabled.
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendPersonalized(PersonalizedMessageData $message): ?SendSmsData
    {
        if (config('usms.is_sending_disabled')) {
            return null;
        }

        /** @var SendSmsData $dto */
        $dto = $this->dispatch(new SendPersonalizedSmsRequest($message, $this->timezone()));

        return $dto;
    }

    /**
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function deliveryReport(int $smsId): ReportData
    {
        /** @var ReportData $dto */
        $dto = $this->dispatch(new DeliveryReportRequest($smsId));

        return $dto;
    }

    /**
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function balance(): SmsBalanceData
    {
        /** @var SmsBalanceData $dto */
        $dto = $this->dispatch(new SmsBalanceRequest);

        return $dto;
    }

    /**
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function createBrandName(string $brandName): BrandNameCreateData
    {
        /** @var BrandNameCreateData $dto */
        $dto = $this->dispatch(new CreateBrandNameRequest($brandName));

        return $dto;
    }

    /**
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function deleteBrandName(int $brandId): BrandNameDeleteData
    {
        /** @var BrandNameDeleteData $dto */
        $dto = $this->dispatch(new DeleteBrandNameRequest($brandId));

        return $dto;
    }

    /**
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function listBrandName(): BrandNameData
    {
        /** @var BrandNameData $dto */
        $dto = $this->dispatch(new ListBrandNameRequest);

        return $dto;
    }

    /**
     * Verify, parse, and return an incoming uBill callback.
     *
     * Pass the callback's query string parameters. Do whatever you want with
     * the returned DTO — update the message, enqueue a job, etc.
     *
     * @param  array<string, mixed>  $query
     *
     * @throws InvalidCallbackKeyException
     * @throws InvalidCallbackPayloadException
     */
    public function handleCallback(array $query): DeliveryUpdatedData|BrandActivatedData|UnknownCallbackData
    {
        return $this->callbackHandler->handle($query);
    }

    /**
     * Dispatch a Saloon request through the connector and unwrap errors.
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    protected function dispatch(Request $request): object
    {
        $dto = $this->connector->send($request)->dto();

        if ($dto instanceof ErrorData) {
            throw USmsException::fromError($dto);
        }

        return $dto;
    }

    protected function timezone(): string
    {
        return (string) config('usms.timezone', self::PROVIDER_TIMEZONE);
    }

    /**
     * @return array{
     *   symbols_count:int,
     *   sms_spend:int,
     *   remaining_to_next_part:int,
     *   max_sms_parts:int
     * }
     */
    public function countSMSCharacters(?string $text): array
    {
        $len = mb_strlen($text, 'UTF-8');

        $isUnicode = $this->containsGeorgian($text);

        [$singleLimit, $multiLimit] = $isUnicode
            ? [self::SINGLE_UNICODE_LIMIT, self::MULTI_UNICODE_LIMIT]
            : [self::SINGLE_LIMIT, self::MULTI_LIMIT];

        $parts = match (true) {
            $len === 0 => 0,
            $len <= $singleLimit => 1,
            default => (int) ceil($len / $multiLimit),
        };

        $remainingToNext = match (true) {
            $len === 0 => $singleLimit,
            $len <= $singleLimit => $singleLimit - $len,
            default => ($len % $multiLimit) === 0 ? $multiLimit : ($multiLimit - ($len % $multiLimit)),
        };

        $partsCapped = min($parts, self::MAX_SMS_PARTS);

        return [
            'symbols_count' => $len,
            'sms_spend' => $partsCapped,
            'remaining_to_next_part' => $remainingToNext,
            'max_sms_parts' => self::MAX_SMS_PARTS,
        ];
    }

    public function containsGeorgian(?string $text): bool
    {
        return preg_match('/[\x{10A0}-\x{10FF}]/u', $text) === 1;
    }

    public function characterLimit(?string $text): int
    {
        return $this->containsGeorgian($text)
            ? self::MAX_SMS_PARTS * self::MULTI_UNICODE_LIMIT
            : self::MAX_SMS_PARTS * self::MULTI_LIMIT;
    }
}
