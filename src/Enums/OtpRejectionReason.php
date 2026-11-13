<?php

namespace ClassAtlas\USms\Enums;

/**
 * Why uBill refused one recipient of a login-code (`otp`) send.
 */
enum OtpRejectionReason: string
{
    /**
     * The number got a login code too recently.
     */
    case OTP_COOLDOWN = 'otpCooldown';

    /**
     * The number has had as many login codes as uBill allows it.
     */
    case OTP_NUMBER_LIMIT = 'otpNumberLimit';
}
