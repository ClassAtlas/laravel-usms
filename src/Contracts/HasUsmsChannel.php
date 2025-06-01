<?php

namespace ClassAtlas\USms\Contracts;

interface HasUsmsChannel
{
    /**
     * Get the SMS representation of the notification.
     *
     * @return array{brandID: int, numbers: array{0: string}, text: string, stopList: bool}
     */
    public function toUSms(object $notifiable): array;
}
