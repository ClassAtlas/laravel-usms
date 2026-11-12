<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\DataObjects\Responses\Inbound\InboundMessageData;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use InvalidArgumentException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use UnexpectedValueException;

/**
 * One incoming message by its `eventID`, with no date limit. Needs the
 * `sms.inbound` permission on the uBill API application.
 */
class InboundMessageRequest extends Request
{
    use TransformsErrorResponse;

    protected Method $method = Method::GET;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        protected string $eventId,
    ) {
        // An empty id would ask for `/v1/sms/inbound/`, the list, and read
        // its first message as the one asked for.
        if (trim($eventId) === '') {
            throw new InvalidArgumentException('An incoming message is read by its eventID, and none was given.');
        }
    }

    public function resolveEndpoint(): string
    {
        return '/v1/sms/inbound/'.rawurlencode($this->eventId);
    }

    /**
     * @throws UnexpectedValueException
     */
    public function createDtoFromResponse(Response $response): InboundMessageData|ErrorData
    {
        if ($error = $this->errorFromResponse($response)) {
            return $error;
        }

        $result = $response->json('result');
        $message = is_array($result) ? reset($result) : null;

        if (! is_array($message)) {
            throw new UnexpectedValueException('uBill answered without the incoming message it was asked for.');
        }

        return InboundMessageData::fromPayload($message);
    }
}
