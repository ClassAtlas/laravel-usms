<?php

namespace ClassAtlas\USms\Contracts;

use BackedEnum;

/**
 * A uBill response code. The provider reuses the same numbers with a different
 * meaning on every endpoint, so each endpoint carries its own enum.
 */
interface ResponseCode extends BackedEnum {}
