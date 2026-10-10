<?php

use ClassAtlas\USms\Enums\DeliveryStatus;

it('treats only a settled outcome as final', function (DeliveryStatus $status, bool $final): void {
    expect($status->isFinal())->toBe($final);
})->with([
    'sent' => [DeliveryStatus::SENT, false],
    'received' => [DeliveryStatus::RECEIVED, true],
    'not delivered' => [DeliveryStatus::NOT_DELIVERED, true],
    'awaiting status' => [DeliveryStatus::AWAITING_STATUS, false],
    'error' => [DeliveryStatus::ERROR, true],
]);

it('counts only RECEIVED as delivered', function (): void {
    $delivered = array_filter(DeliveryStatus::cases(), fn (DeliveryStatus $status): bool => $status->isDelivered());

    expect(array_values($delivered))->toBe([DeliveryStatus::RECEIVED]);
});
