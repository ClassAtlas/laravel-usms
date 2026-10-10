<?php

namespace ClassAtlas\USms\Traits;

use Carbon\CarbonImmutable;
use DateTimeInterface;

trait BuildsSendOptions
{
    /**
     * Options are left out of the body unless set, so a plain send is byte for
     * byte what it was before they existed.
     *
     * @return array{otp?: true, sendTime?: string, callbackUrl?: string}
     */
    protected function sendOptions(bool $otp, ?DateTimeInterface $sendAt, ?string $callbackUrl, string $timezone): array
    {
        $options = [];

        if ($otp) {
            $options['otp'] = true;
        }

        if ($sendAt !== null) {
            $options['sendTime'] = CarbonImmutable::instance($sendAt)->setTimezone($timezone)->format('Y-m-d H:i');
        }

        if ($callbackUrl !== null && $callbackUrl !== '') {
            $options['callbackUrl'] = $callbackUrl;
        }

        return $options;
    }
}
