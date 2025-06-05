<?php

namespace ClassAtlas\USms\Events;

use ClassAtlas\USms\DataObjects\USmsWasSentData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class USmsWasSent
{
    use Dispatchable, SerializesModels;

    public USmsWasSentData $USmsWasSentData;

    public function __construct(USmsWasSentData $USmsWasSentData)
    {
        $this->USmsWasSentData = $USmsWasSentData;
    }
}
