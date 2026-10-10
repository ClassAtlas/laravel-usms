<?php

namespace ClassAtlas\USms\Enums;

use ClassAtlas\USms\Contracts\ResponseCode;

enum BrandNameDeleteCode: int implements ResponseCode
{
    case NOT_FOUND = 10;
    case JSON_ERROR = 90;
    case GENERAL_ERROR = 99;
}
