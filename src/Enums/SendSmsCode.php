<?php

namespace ClassAtlas\USms\Enums;

use ClassAtlas\USms\Contracts\ResponseCode;

enum SendSmsCode: int implements ResponseCode
{
    case BRAND_NOT_FOUND = 10;
    case NUMBERS_NOT_FOUND = 20;
    case EMPTY_TEXT = 30;
    case INSUFFICIENT_BALANCE = 40;
    case NO_VALID_NUMBERS = 50;
    case INVALID_SEND_TIME = 60;
    case JSON_ERROR = 90;
    case GENERAL_ERROR = 99;
}
