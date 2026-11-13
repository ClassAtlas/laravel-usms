<?php

namespace ClassAtlas\USms\DataObjects;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

/**
 * Which incoming messages to read. Without dates uBill returns the last 24
 * hours, oldest first.
 */
class InboundFilterData extends Data
{
    public const int MAX_LIMIT = 100;

    public function __construct(
        /**
         * One whole calendar day, the one this date names, as uBill counts it
         * in Tbilisi. Not together with `from` or `to`.
         */
        public ?DateTimeInterface $date = null,
        /**
         * Inclusive start; 24 hours before the end when left out.
         */
        public ?DateTimeInterface $from = null,
        /**
         * Exclusive end; now when left out.
         */
        public ?DateTimeInterface $to = null,
        /**
         * Messages per page, 1 to 100. uBill's default is 50.
         */
        public ?int $limit = null,
        /**
         * The previous page's `nextCursor`, to read the messages after it.
         */
        public ?string $cursor = null,
    ) {
        if ($date !== null && ($from !== null || $to !== null)) {
            throw new InvalidArgumentException('Filter incoming messages by a date or by from and to, not both.');
        }

        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidArgumentException('The start of an incoming-message window is after its end.');
        }

        if ($limit !== null && ($limit < 1 || $limit > self::MAX_LIMIT)) {
            throw new InvalidArgumentException('An incoming-message page holds 1 to '.self::MAX_LIMIT.' messages.');
        }
    }

    /**
     * The query uBill reads: a date as the day it names, a window as RFC 3339
     * timestamps with their offset.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'date' => $this->date?->format('Y-m-d'),
            'from' => $this->from === null ? null : CarbonImmutable::instance($this->from)->format('Y-m-d\TH:i:s.uP'),
            'to' => $this->to === null ? null : CarbonImmutable::instance($this->to)->format('Y-m-d\TH:i:s.uP'),
            'limit' => $this->limit,
            'cursor' => $this->cursor,
        ], fn (string|int|null $value): bool => $value !== null);
    }
}
