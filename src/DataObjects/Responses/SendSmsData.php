<?php

namespace ClassAtlas\USms\DataObjects\Responses;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class SendSmsData extends Data
{
    /**
     * @param  list<RejectedNumberData>  $rejectedNumbers
     */
    public function __construct(
        public ?int $smsID = null,
        public string $message = '',
        /**
         * How many recipients uBill queued. Null when uBill does not say,
         * which it only does for a send it filtered.
         */
        public ?int $acceptedCount = null,
        public int $rejectedCount = 0,
        /**
         * The recipients uBill refused while taking the rest, today only by
         * its protection for login codes.
         */
        #[DataCollectionOf(RejectedNumberData::class)]
        public array $rejectedNumbers = [],
    ) {}

    /**
     * Whether uBill took the send but refused some of its recipients.
     */
    public function isPartial(): bool
    {
        return $this->rejectedCount > 0 || $this->rejectedNumbers !== [];
    }

    /**
     * @param  array<mixed>  $answer  uBill's answer to a send it accepted.
     */
    public static function fromAnswer(array $answer): self
    {
        return new self(
            smsID: is_numeric($answer['smsID'] ?? null) ? (int) $answer['smsID'] : null,
            message: is_string($answer['message'] ?? null) ? $answer['message'] : '',
            acceptedCount: is_numeric($answer['acceptedCount'] ?? null) ? (int) $answer['acceptedCount'] : null,
            rejectedCount: is_numeric($answer['rejectedCount'] ?? null) ? (int) $answer['rejectedCount'] : 0,
            rejectedNumbers: RejectedNumberData::listFrom($answer['rejectedNumbers'] ?? null),
        );
    }
}
