<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use ClassAtlas\USms\Enums\OtpRejectionReason;
use Spatie\LaravelData\Data;

/**
 * One recipient uBill refused in a send it otherwise took. It was neither
 * queued nor charged; uBill asks that only these be sent again, never the
 * whole batch, which would send the accepted ones twice.
 */
class RejectedNumberData extends Data
{
    public function __construct(
        /**
         * The recipient's zero-based position in the request.
         */
        public int $index,
        public string $number,
        public int $statusID,
        /**
         * uBill's own words, kept even when this version has no case for them.
         */
        public string $reason,
        /**
         * Seconds until this number may be sent a login code again.
         */
        public ?int $retryAfter = null,
    ) {}

    /**
     * Null when uBill gives a reason this version does not know.
     */
    public function rejectionReason(): ?OtpRejectionReason
    {
        return OtpRejectionReason::tryFrom($this->reason);
    }

    /**
     * Reads uBill's `rejectedNumbers` list. An entry without an index or a
     * number is left out: it names nobody to send again.
     *
     * @return list<self>
     */
    public static function listFrom(mixed $rejectedNumbers): array
    {
        if (! is_array($rejectedNumbers)) {
            return [];
        }

        $list = [];

        foreach ($rejectedNumbers as $entry) {
            if (! is_array($entry) || ! is_numeric($entry['index'] ?? null) || ! is_scalar($entry['number'] ?? null)) {
                continue;
            }

            $list[] = new self(
                index: (int) $entry['index'],
                number: (string) $entry['number'],
                statusID: is_numeric($entry['statusID'] ?? null) ? (int) $entry['statusID'] : 0,
                reason: is_string($entry['reason'] ?? null) ? $entry['reason'] : '',
                retryAfter: is_numeric($entry['retryAfter'] ?? null) ? (int) $entry['retryAfter'] : null,
            );
        }

        return $list;
    }
}
