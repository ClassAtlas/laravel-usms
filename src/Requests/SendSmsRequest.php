<?php

namespace ClassAtlas\USms\Requests;

use ClassAtlas\USms\DataObjects\SendSmsData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SendSmsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string[]  $numbers
     */
    public function __construct(
        protected int $brandId,
        protected array $numbers,
        protected string $text,
        protected bool $stopList,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/sms/send';
    }

    /**
     * @return array{brandID: int, numbers: string[], text: string, stopList: bool}
     */
    public function defaultBody(): array
    {
        return [
            'brandID' => $this->brandId,
            'numbers' => $this->numbers,
            'text' => $this->text,
            'stopList' => $this->stopList,
        ];
    }

    public function createDtoFromResponse(Response $response): SendSmsData
    {
        return SendSmsData::from($response->json());
    }
}
