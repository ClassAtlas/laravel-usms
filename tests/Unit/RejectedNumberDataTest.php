<?php

use ClassAtlas\USms\DataObjects\Responses\RejectedNumberData;
use ClassAtlas\USms\Enums\OtpRejectionReason;

it('reads uBill\'s rejected numbers, converting numbers sent as text', function (): void {
    $list = RejectedNumberData::listFrom([
        ['index' => '2', 'number' => 555000000, 'statusID' => '47', 'reason' => 'otpCooldown', 'retryAfter' => '30'],
    ]);

    expect($list)->toHaveCount(1)
        ->and($list[0]->index)->toBe(2)
        ->and($list[0]->number)->toBe('555000000')
        ->and($list[0]->statusID)->toBe(47)
        ->and($list[0]->rejectionReason())->toBe(OtpRejectionReason::OTP_COOLDOWN)
        ->and($list[0]->retryAfter)->toBe(30);
});

it('keeps a reason this version does not know as uBill wrote it', function (): void {
    $list = RejectedNumberData::listFrom([
        ['index' => 0, 'number' => '555000000', 'statusID' => 47, 'reason' => 'otpSomethingNew'],
    ]);

    expect($list[0]->reason)->toBe('otpSomethingNew')
        ->and($list[0]->rejectionReason())->toBeNull()
        ->and($list[0]->retryAfter)->toBeNull();
});

it('leaves out an entry that names nobody', function (mixed $rejectedNumbers): void {
    expect(RejectedNumberData::listFrom($rejectedNumbers))->toBe([]);
})->with([
    'not a list' => ['otpCooldown'],
    'no index' => [[['number' => '555000000', 'reason' => 'otpCooldown']]],
    'no number' => [[['index' => 0, 'reason' => 'otpCooldown']]],
    'not an entry' => [['555000000']],
]);
