<?php

use ClassAtlas\USms\USms;

it('counts SMS parts the way they are billed', function (string $text, int $parts): void {
    expect((new USms)->countSMSCharacters($text)['sms_spend'])->toBe($parts);
})->with([
    'empty' => ['', 0],
    'latin, one part' => [str_repeat('a', 160), 1],
    'latin, two parts' => [str_repeat('a', 161), 2],
    'georgian, one part' => [str_repeat('ა', 70), 1],
    'georgian, two parts' => [str_repeat('ა', 71), 2],
    'capped at the maximum' => [str_repeat('ა', 1000), USms::MAX_SMS_PARTS],
]);
