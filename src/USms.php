<?php

namespace ClassAtlas\USms;

use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\InboundReceivedData;
use ClassAtlas\USms\DataObjects\Callbacks\UnknownCallbackData;
use ClassAtlas\USms\DataObjects\InboundFilterData;
use ClassAtlas\USms\DataObjects\MessageData;
use ClassAtlas\USms\DataObjects\PersonalizedMessageData;
use ClassAtlas\USms\DataObjects\Responses\BrandNameCreateData;
use ClassAtlas\USms\DataObjects\Responses\BrandNameDeleteData;
use ClassAtlas\USms\DataObjects\Responses\BrandNames\BrandNameData;
use ClassAtlas\USms\DataObjects\Responses\DeliveryReport\ReportData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMessageData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundPageData;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\DataObjects\Responses\SmsBalanceData;
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Requests\CreateBrandNameRequest;
use ClassAtlas\USms\Requests\DeleteBrandNameRequest;
use ClassAtlas\USms\Requests\DeliveryReportRequest;
use ClassAtlas\USms\Requests\InboundMessageRequest;
use ClassAtlas\USms\Requests\InboundSmsRequest;
use ClassAtlas\USms\Requests\ListBrandNameRequest;
use ClassAtlas\USms\Requests\SendPersonalizedSmsRequest;
use ClassAtlas\USms\Requests\SendSmsRequest;
use ClassAtlas\USms\Requests\SmsBalanceRequest;
use ClassAtlas\USms\Support\Settings;
use Generator;
use Illuminate\Support\LazyCollection;
use Psr\Http\Client\NetworkExceptionInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use UnexpectedValueException;

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
                key: Settings::string('usms.callback_key'),
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
     * @throws NetworkExceptionInterface
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
     * @throws NetworkExceptionInterface
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
     * @throws NetworkExceptionInterface
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
     * @throws NetworkExceptionInterface
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
     * @throws NetworkExceptionInterface
     * @throws RequestException
     */
    public function createBrandName(string $brandName, string $description): BrandNameCreateData
    {
        /** @var BrandNameCreateData $dto */
        $dto = $this->dispatch(new CreateBrandNameRequest($brandName, $description));

        return $dto;
    }

    /**
     * @throws USmsException
     * @throws FatalRequestException
     * @throws NetworkExceptionInterface
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
     * @throws NetworkExceptionInterface
     * @throws RequestException
     */
    public function listBrandName(): BrandNameData
    {
        /** @var BrandNameData $dto */
        $dto = $this->dispatch(new ListBrandNameRequest);

        return $dto;
    }

    /**
     * One page of the messages sent to the company's short number. Without a
     * filter: the last 24 hours, oldest first. Needs the `sms.inbound`
     * permission on the uBill API application.
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws NetworkExceptionInterface
     * @throws RequestException
     */
    public function inbound(?InboundFilterData $filter = null): InboundPageData
    {
        /** @var InboundPageData $dto */
        $dto = $this->dispatch(new InboundSmsRequest($filter, $this->timezone()));

        return $dto;
    }

    /**
     * One incoming message by its `eventID`. uBill answers HTTP 404, thrown as
     * a USmsException, for an id it does not know.
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws NetworkExceptionInterface
     * @throws RequestException
     */
    public function inboundMessage(string $eventId): InboundMessageData
    {
        /** @var InboundMessageData $dto */
        $dto = $this->dispatch(new InboundMessageRequest($eventId));

        return $dto;
    }

    /**
     * Every incoming message the filter selects, page by page as they are
     * read. The first page's window is kept for the pages after it, as uBill
     * asks, so messages arriving meanwhile do not shift it.
     *
     * @return LazyCollection<int, InboundMessageData>
     */
    public function inboundAll(?InboundFilterData $filter = null): LazyCollection
    {
        return LazyCollection::make(function () use ($filter): Generator {
            $page = $this->inbound($filter);

            foreach ($page->result as $message) {
                yield $message;
            }

            while ($page->meta->hasMore && $page->meta->nextCursor !== null) {
                $cursor = $page->meta->nextCursor;

                $page = $this->inbound(new InboundFilterData(
                    from: $page->meta->from,
                    to: $page->meta->to,
                    limit: $filter?->limit,
                    cursor: $cursor,
                ));

                foreach ($page->result as $message) {
                    yield $message;
                }

                if ($page->meta->nextCursor === $cursor) {
                    return;
                }
            }
        });
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
     * Verify, parse, and return a callback uBill POSTs as JSON
     * (`sms.inbound.received`). Pass the decoded body and the
     * `X-Webhook-Token` header.
     *
     * @param  array<mixed>  $body
     *
     * @throws InvalidCallbackKeyException
     * @throws InvalidCallbackPayloadException
     */
    public function handleJsonCallback(array $body, ?string $token = null): InboundReceivedData|UnknownCallbackData
    {
        return $this->callbackHandler->handleJson($body, $token);
    }

    /**
     * Dispatch a Saloon request through the connector and unwrap errors.
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws NetworkExceptionInterface
     * @throws RequestException
     */
    protected function dispatch(Request $request): object
    {
        $dto = $this->connector->send($request)->dto();

        if ($dto instanceof ErrorData) {
            throw USmsException::fromError($dto);
        }

        if (! is_object($dto)) {
            throw new UnexpectedValueException($request::class.' produced no response object.');
        }

        return $dto;
    }

    protected function timezone(): string
    {
        return Settings::string('usms.timezone', self::PROVIDER_TIMEZONE);
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
        $text ??= '';
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
        return preg_match('/[\x{10A0}-\x{10FF}]/u', $text ?? '') === 1;
    }

    public function characterLimit(?string $text): int
    {
        return $this->containsGeorgian($text)
            ? self::MAX_SMS_PARTS * self::MULTI_UNICODE_LIMIT
            : self::MAX_SMS_PARTS * self::MULTI_LIMIT;
    }
}
