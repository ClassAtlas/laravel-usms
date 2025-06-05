<?php

namespace ClassAtlas\USms\Channels;

use ClassAtlas\USms\Contracts\HasUsmsChannel;
use ClassAtlas\USms\DataObjects\USmsWasSentData;
use ClassAtlas\USms\Events\USmsWasSent;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use USms;

class USmsChannel
{
    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function send(mixed $notifiable, HasUsmsChannel $notification): void
    {
        $message = $notification->toUSms($notifiable);

        $SendSmsData = USms::sendSms(
            $message->brandId,
            $message->numbers,
            $message->text,
            $message->stopList
        );

        $uSmsWasSentData = USmsWasSentData::from([
            'notificationId' => $message->notificationId,
            'sendSmsData' => $SendSmsData,
        ]);

        USmsWasSent::dispatch($uSmsWasSentData);
    }
}
