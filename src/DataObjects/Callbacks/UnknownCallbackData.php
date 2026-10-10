<?php

namespace ClassAtlas\USms\DataObjects\Callbacks;

use Spatie\LaravelData\Data;

/**
 * An event this package does not model yet. uBill sends every future event to
 * the same URL, so the host should acknowledge it rather than fail.
 */
class UnknownCallbackData extends Data
{
    /**
     * @param  array<string, mixed>  $payload  The query string without the key.
     */
    public function __construct(
        public ?string $event,
        public array $payload,
    ) {}
}
