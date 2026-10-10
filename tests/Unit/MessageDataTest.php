<?php

use ClassAtlas\USms\DataObjects\MessageData;

it('builds from the array shape notifications pass', function (): void {
    $message = MessageData::from([
        'brandId' => 29,
        'numbers' => ['995555000000'],
        'text' => 'Hello',
        'stopList' => false,
        'notificationId' => 'notif-1',
    ]);

    expect($message->brandId)->toBe(29)
        ->and($message->numbers)->toBe(['995555000000'])
        ->and($message->notificationId)->toBe('notif-1')
        ->and($message->otp)->toBeFalse()
        ->and($message->sendAt)->toBeNull()
        ->and($message->callbackUrl)->toBeNull();
});

it('carries no notification id unless one is given', function (): void {
    expect((new MessageData(29, ['995555000000'], 'Hello'))->notificationId)->toBeNull();
});
