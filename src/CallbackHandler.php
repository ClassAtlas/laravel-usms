<?php

namespace ClassAtlas\USms;

use Carbon\CarbonImmutable;
use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\InboundReceivedData;
use ClassAtlas\USms\DataObjects\Callbacks\UnknownCallbackData;
use ClassAtlas\USms\Enums\BrandNameAuthorized;
use ClassAtlas\USms\Enums\CallbackEvent;
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;
use Throwable;

class CallbackHandler
{
    public function __construct(
        private readonly CallbackKeyVerifier $verifier,
        private readonly string $timezone = USms::PROVIDER_TIMEZONE,
    ) {}

    /**
     * Verify, parse, and return a typed callback payload.
     *
     * @param  array<string, mixed>  $query  The callback's query string parameters.
     *
     * @throws InvalidCallbackKeyException
     * @throws InvalidCallbackPayloadException
     */
    public function handle(array $query): DeliveryUpdatedData|BrandActivatedData|UnknownCallbackData
    {
        $key = $query['key'] ?? '';

        $this->verifier->verify(is_string($key) ? $key : '');

        $event = is_string($query['event'] ?? null) ? $query['event'] : null;

        return match (CallbackEvent::tryFrom((string) $event)) {
            CallbackEvent::DELIVERY_UPDATED => new DeliveryUpdatedData(
                smsID: $this->integer($query, 'smsID', (string) $event),
                number: $this->string($query, 'number', (string) $event),
                statusID: $this->integer($query, 'statusID', (string) $event),
                date: $this->date($query),
            ),
            CallbackEvent::BRAND_ACTIVATED => new BrandActivatedData(
                brandID: $this->integer($query, 'brandID', (string) $event),
                brandName: $this->string($query, 'brandName', (string) $event),
                authorized: is_numeric($query['authorized'] ?? null)
                    ? BrandNameAuthorized::tryFrom((int) $query['authorized'])
                    : null,
                date: $this->date($query),
            ),
            // uBill POSTs incoming messages as JSON: see handleJson().
            CallbackEvent::INBOUND_RECEIVED, null => new UnknownCallbackData(
                event: $event,
                payload: array_diff_key($query, ['key' => true]),
            ),
        };
    }

    /**
     * Verify, parse, and return a callback uBill POSTs as JSON: today only
     * `sms.inbound.received`. The key comes in the `X-Webhook-Token` header,
     * and in the body as well.
     *
     * @param  array<mixed>  $body  The decoded JSON body.
     *
     * @throws InvalidCallbackKeyException
     * @throws InvalidCallbackPayloadException
     */
    public function handleJson(array $body, ?string $token = null): InboundReceivedData|UnknownCallbackData
    {
        $key = $token !== null && $token !== '' ? $token : ($body['key'] ?? '');

        $this->verifier->verify(is_string($key) ? $key : '');

        $event = is_string($body['event'] ?? null) ? $body['event'] : null;

        if (CallbackEvent::tryFrom((string) $event) === CallbackEvent::INBOUND_RECEIVED) {
            return InboundReceivedData::fromCallback($body, (string) $event);
        }

        /** @var array<string, mixed> $payload */
        $payload = array_diff_key($body, ['key' => true]);

        return new UnknownCallbackData(event: $event, payload: $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     *
     * @throws InvalidCallbackPayloadException
     */
    private function integer(array $query, string $parameter, string $event): int
    {
        $value = $query[$parameter] ?? null;

        if (! is_scalar($value) || preg_match('/^\d+$/', (string) $value) !== 1) {
            throw InvalidCallbackPayloadException::forParameter($event, $parameter);
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $query
     *
     * @throws InvalidCallbackPayloadException
     */
    private function string(array $query, string $parameter, string $event): string
    {
        $value = $query[$parameter] ?? null;

        if (! is_scalar($value) || trim((string) $value) === '') {
            throw InvalidCallbackPayloadException::forParameter($event, $parameter);
        }

        return trim((string) $value);
    }

    /**
     * Lenient on purpose: uBill does not document the timestamp's timezone or
     * guarantee its format, and a delivery status is worth keeping without one.
     *
     * @param  array<string, mixed>  $query
     */
    private function date(array $query): ?CarbonImmutable
    {
        $value = $query['date'] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $value, $this->timezone) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
