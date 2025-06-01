<?php

namespace ClassAtlas\USms\Channels;

use ClassAtlas\USms\Contracts\HasUsmsChannel;
use USms;

class USmsChannel
{
    public function send(mixed $notifiable, HasUsmsChannel $notification): void
    {
        $message = $notification->toUSms($notifiable);

        USms::sendSms(
            $message['brandID'],
            $message['numbers'],
            $message['text'],
            $message['stopList'],
        );
    }
}
