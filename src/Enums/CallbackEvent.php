<?php

namespace ClassAtlas\USms\Enums;

enum CallbackEvent: string
{
    case DELIVERY_UPDATED = 'sms.delivery.updated';
    case BRAND_ACTIVATED = 'sms.brand.activated';
}
