<?php

namespace ClassAtlas\USms\Tests\Support;

use ClassAtlas\USms\Contracts\HasUsmsChannel;
use ClassAtlas\USms\DataObjects\MessageData;
use Illuminate\Notifications\Notification;

class TestSmsNotification extends Notification implements HasUsmsChannel
{
    public function __construct(
        private readonly int $brandId = 1,
        private readonly string $text = 'Hello',
    ) {}

    public function toUSms(object $notifiable): MessageData
    {
        return MessageData::from([
            'brandId' => $this->brandId,
            'numbers' => [RecordingMode::PLACEHOLDER_NUMBER],
            'text' => $this->text,
            'stopList' => false,
            'notificationId' => 'notif-1',
        ]);
    }
}
