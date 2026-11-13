<?php

namespace ClassAtlas\USms\DataObjects\Responses\Inbound;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class InboundPageData extends Data
{
    public function __construct(
        public InboundMetaData $meta,
        /** @var Collection<int, InboundMessageData> */
        public Collection $result,
    ) {}
}
