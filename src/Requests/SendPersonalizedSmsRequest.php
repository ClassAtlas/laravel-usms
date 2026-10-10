<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\Contracts\ResponseCode;
use ClassAtlas\USms\DataObjects\PersonalizedMessageData;
use ClassAtlas\USms\DataObjects\RecipientData;
use ClassAtlas\USms\DataObjects\Responses\ErrorData;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\Enums\SendSmsCode;
use ClassAtlas\USms\Traits\BuildsSendOptions;
use ClassAtlas\USms\Traits\TransformsErrorResponse;
use ClassAtlas\USms\USms;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * The same endpoint as {@see SendSmsRequest}, with a text per number. uBill
 * answers the whole batch with a single smsID.
 */
class SendPersonalizedSmsRequest extends Request implements HasBody
{
    use BuildsSendOptions, HasJsonBody, TransformsErrorResponse;

    protected Method $method = Method::POST;

    public function __construct(
        protected PersonalizedMessageData $message,
        protected string $timezone = USms::PROVIDER_TIMEZONE,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/send';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return [
            'brandID' => $this->message->brandId,
            'numbers' => array_map(
                fn (RecipientData $recipient): array => ['number' => $recipient->number, 'text' => $recipient->text],
                array_values($this->message->recipients),
            ),
            'stopList' => $this->message->stopList,
            ...$this->sendOptions(
                $this->message->otp,
                $this->message->sendAt,
                $this->message->callbackUrl,
                $this->timezone,
            ),
        ];
    }

    public function createDtoFromResponse(Response $response): SendSmsData|ErrorData
    {
        return $this->errorFromResponse($response) ?? SendSmsData::from($response->json());
    }

    protected function responseCode(int $statusId): ?ResponseCode
    {
        return SendSmsCode::tryFrom($statusId);
    }
}
