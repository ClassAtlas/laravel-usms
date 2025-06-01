<?php

namespace ClassAtlas\USms\Enums;

enum BrandNameAuthorized: int
{
    case PENDING = 0;
    case AUTHORIZED = 1;
    case CANCELED = 2;
}
