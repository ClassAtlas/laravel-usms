<?php

namespace ClassAtlas\USms\Enums;

use ClassAtlas\USms\Contracts\ResponseCode;

enum BrandNameCreateCode: int implements ResponseCode
{
    case EMPTY_NAME = 10;
    case INVALID_LENGTH = 20;
    case FORBIDDEN_CHARACTERS = 30;
    case ALREADY_EXISTS = 40;
    case PREVIOUS_PENDING = 50;
    case DESCRIPTION_REQUIRED = 60;
    case DESCRIPTION_TOO_LONG = 61;
    case JSON_ERROR = 90;
    case GENERAL_ERROR = 99;
}
