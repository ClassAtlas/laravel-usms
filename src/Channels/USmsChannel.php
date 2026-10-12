<?php

namespace ClassAtlas\USms\Channels;

use ClassAtlas\USms\Contracts\HasUsmsChannel;
use ClassAtlas\USms\DataObjects\MessageData;
use ClassAtlas\USms\DataObjects\Responses\SendSmsData;
use ClassAtlas\USms\DataObjects\USmsSendFailedData;
use ClassAtlas\USms\DataObjects\USmsWasSentData;
use ClassAtlas\USms\Events\USmsSendFailed;
use ClassAtlas\USms\Events\USmsWasSent;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRejectedException;
use ClassAtlas\USms\USms;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class USmsChannel
{
    public function __construct(protected USms $usms) {}

    /**
     * A send uBill refuses is announced as {@see USmsSendFailed} and not
     * thrown: asking again gets the same answer. Everything else — a timeout,
     * a 5xx, a rate limit — is thrown so the queue retries it.
     *
     * @throws USmsException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function send(mixed $notifiable, HasUsmsChannel $notification): void
    {
        $message = $notification->toUSms($notifiable);

        if ((int) $message->brandId <= 0) {
            report(new \RuntimeException('USmsChannel invoked without a configured brandId; skipping send.'));

            return;
        }

        // A message with nobody to go to is not sent: uBill would refuse it,
        // and the refusal would read as something having gone wrong.
        if ($message->numbers === []) {
            return;
        }

        try {
            $sendSmsData = $this->usms->send($message);
        } catch (USmsRejectedException $e) {
            USmsSendFailed::dispatch(new USmsSendFailedData(
                notificationId: $message->notificationId,
                notificationType: $notification::class,
                statusID: $e->statusId(),
                message: $e->getMessage(),
                code: $e->responseCode(),
            ));

            return;
        }

        if ($sendSmsData === null) {
            event($this->wasSent($message, $notification, null));

            return;
        }

        // uBill has the message. Nothing from here on may fail the job: the
        // retry would send, and bill, the same SMS a second time.
        try {
            event($this->wasSent($message, $notification, $sendSmsData));
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function wasSent(MessageData $message, HasUsmsChannel $notification, ?SendSmsData $sendSmsData): USmsWasSent
    {
        return new USmsWasSent(USmsWasSentData::from([
            'notificationId' => $message->notificationId,
            'sendSmsData' => $sendSmsData,
            'notificationType' => $notification::class,
            // SMS parts, not messages: the unit uBill bills in, so a listener
            // tallying usage counts what was actually spent.
            'parts' => $this->usms->countSMSCharacters($message->text)['sms_spend'],
        ]));
    }
}
