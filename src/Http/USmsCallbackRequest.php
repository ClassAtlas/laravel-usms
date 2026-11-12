<?php

namespace ClassAtlas\USms\Http;

use ClassAtlas\USms\CallbackHandler;
use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\InboundReceivedData;
use ClassAtlas\USms\DataObjects\Callbacks\UnknownCallbackData;
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;
use ClassAtlas\USms\Exceptions\InvalidCallbackPayloadException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Laravel FormRequest that verifies an incoming uBill callback key and parses
 * it into a typed DTO before the controller action runs: the query string of
 * a GET callback, the JSON body of a POST one (`sms.inbound.received`). uBill
 * sends both to the same URL, so the route accepts both methods.
 *
 *     public function __invoke(USmsCallbackRequest $request)
 *     {
 *         $callback = $request->callback();
 *         // ...
 *         return response('', 200);
 *     }
 *
 * A missing or wrong key aborts with HTTP 403, a known event with a malformed
 * payload with HTTP 422, both before the controller is dispatched. An event
 * this package does not model arrives as UnknownCallbackData.
 */
class USmsCallbackRequest extends FormRequest
{
    protected DeliveryUpdatedData|BrandActivatedData|InboundReceivedData|UnknownCallbackData $parsed;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function callback(): DeliveryUpdatedData|BrandActivatedData|InboundReceivedData|UnknownCallbackData
    {
        return $this->parsed;
    }

    protected function prepareForValidation(): void
    {
        try {
            $handler = $this->container->make(CallbackHandler::class);

            $this->parsed = $this->isMethod('POST')
                ? $handler->handleJson($this->json()->all(), $this->header('X-Webhook-Token'))
                : $handler->handle($this->query());
        } catch (InvalidCallbackKeyException) {
            abort(403, 'Invalid uBill callback key');
        } catch (InvalidCallbackPayloadException $e) {
            abort(422, $e->getMessage());
        }
    }
}
