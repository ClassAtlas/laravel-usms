<?php

namespace ClassAtlas\USms\Enums;

enum USmsStatus: int
{
    case SMS_SENT = 0;
    case RECEIVED = 1;
    case NOT_DELIVERED = 2;
    case AWAITING_STATUS = 3;
    case ERROR = 4;
    case BRAND_ID_NOT_FOUND = 10;
    case NUMBERS_NOT_FOUND = 20;
    case EMPTY_MESSAGE_TEXT = 30;
    case NO_ENOUGH_SMS = 40;
    case VALID_NUMBERS_NOT_FOUND = 50;
    case JSON_ERROR = 90;
    case GENERAL_ERROR = 99;

    public function getLabel(): string
    {
        return match ($this) {
            self::SMS_SENT => 'SMS_SENT',
            self::RECEIVED => 'RECEIVED',
            self::NOT_DELIVERED => 'NOT_DELIVERED',
            self::AWAITING_STATUS => 'AWAITING_STATUS',
            self::ERROR => 'ERROR',
            self::BRAND_ID_NOT_FOUND => 'BRAND_ID_NOT_FOUND',
            self::NUMBERS_NOT_FOUND => 'NUMBERS_NOT_FOUND',
            self::EMPTY_MESSAGE_TEXT => 'EMPTY_MESSAGE_TEXT',
            self::NO_ENOUGH_SMS => 'NO_ENOUGH_SMS',
            self::VALID_NUMBERS_NOT_FOUND => 'VALID_NUMBERS_NOT_FOUND',
            self::JSON_ERROR => 'JSON_ERROR',
            self::GENERAL_ERROR => 'GENERAL_ERROR',
        };
    }
}
