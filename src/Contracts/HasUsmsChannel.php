<?php

namespace ClassAtlas\USms\Contracts;

use ClassAtlas\USms\DataObjects\MessageData;

interface HasUsmsChannel
{
    /**
     * Get the SMS representation of the notification.
     */
    public function toUSms(object $notifiable): MessageData;
}
