<?php

namespace ClassAtlas\USms\Enums;

enum USmsStatus: int
{
    case SMS_SENT = 0;
    case BRAND_ID_NOT_FOUND = 10;
    case NUMBERS_NOT_FOUND = 20;
    case EMPTY_MESSAGE_TEXT = 30;
    case NO_ENOUGH_SMS = 40;
    case VALID_NUMBERS_NOT_FOUND = 50;
    case JSON_ERROR = 90;
    case GENERAL_ERROR = 99;
}
