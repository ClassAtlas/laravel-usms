<?php

namespace ClassAtlas\USms\Events;

use ClassAtlas\USms\DataObjects\USmsSendFailedData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class USmsSendFailed
{
    use Dispatchable, SerializesModels;

    public USmsSendFailedData $USmsSendFailedData;

    public function __construct(USmsSendFailedData $USmsSendFailedData)
    {
        $this->USmsSendFailedData = $USmsSendFailedData;
    }
}
