<?php

namespace ClassAtlas\USms\Contracts;

use ClassAtlas\USms\DataObjects\MessageData;

interface HasUsmsChannel
{
    /**
     * Get the SMS representation of the notification.
     *
     * @param object $notifiable
     * @return MessageData
     */
    public function toUSms(object $notifiable): MessageData;
}
