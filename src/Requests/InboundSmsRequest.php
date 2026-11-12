<?php

namespace ClassAtlas\USms\Requests;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\InboundFilterData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMessageData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMetaData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundPageData;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use ClassAtlas\USms\USms;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;
use UnexpectedValueException;

/**
 * One page of the messages sent to the company's short number. Needs the
 * `sms.inbound` permission on the uBill API application.
 */
class InboundSmsRequest extends Request
{
    use TransformsErrorResponse;

    protected Method $method = Method::GET;

    public function __construct(
        protected ?InboundFilterData $filter = null,
        protected string $timezone = USms::PROVIDER_TIMEZONE,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/inbound';
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        return $this->filter?->toQuery($this->timezone) ?? [];
    }

    /**
     * @throws UnexpectedValueException
     */
    public function createDtoFromResponse(Response $response): InboundPageData|ErrorData
    {
        if ($error = $this->errorFromResponse($response)) {
            return $error;
        }

        $meta = $response->json('meta');
        $result = $response->json('result');

        if (! is_array($meta)) {
            throw new UnexpectedValueException('uBill answered a page of incoming messages without its meta.');
        }

        return new InboundPageData(
            meta: new InboundMetaData(
                from: self::moment($meta['from'] ?? null, 'from'),
                to: self::moment($meta['to'] ?? null, 'to'),
                limit: is_numeric($meta['limit'] ?? null) ? (int) $meta['limit'] : 0,
                count: is_numeric($meta['count'] ?? null) ? (int) $meta['count'] : 0,
                hasMore: is_scalar($meta['hasMore'] ?? null) && filter_var($meta['hasMore'], FILTER_VALIDATE_BOOL),
                nextCursor: is_string($meta['nextCursor'] ?? null) && $meta['nextCursor'] !== '' ? $meta['nextCursor'] : null,
            ),
            result: collect(is_array($result) ? array_values($result) : [])
                ->map(fn (mixed $message): InboundMessageData => InboundMessageData::fromPayload(is_array($message) ? $message : [])),
        );
    }

    /**
     * @throws UnexpectedValueException
     */
    private static function moment(mixed $value, string $field): CarbonImmutable
    {
        $unreadable = new UnexpectedValueException("uBill answered a page of incoming messages without a readable meta.{$field}.");

        if (! is_string($value) || $value === '') {
            throw $unreadable;
        }

        try {
            return CarbonImmutable::parse($value, 'UTC')->setTimezone('UTC');
        } catch (Throwable) {
            throw $unreadable;
        }
    }
}
