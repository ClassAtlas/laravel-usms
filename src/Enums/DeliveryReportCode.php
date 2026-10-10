<?php

namespace ClassAtlas\USms\Enums;

use ClassAtlas\USms\Contracts\ResponseCode;

/**
 * Not in the uBill docs: observed on the live API for an smsID it does not know.
 */
enum DeliveryReportCode: int implements ResponseCode
{
    case SMS_NOT_FOUND = 12;
}
