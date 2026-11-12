<?php

namespace ClassAtlas\USms\DataObjects\Responses\Inbound;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * The window and position of one page of incoming messages. To read the next
 * page, pass `from`, `to` and `nextCursor` back, so the window stays fixed.
 */
class InboundMetaData extends Data
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public int $limit,
        public int $count,
        public bool $hasMore,
        public ?string $nextCursor = null,
    ) {}
}
