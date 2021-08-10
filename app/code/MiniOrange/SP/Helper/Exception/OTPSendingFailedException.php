<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that an error occurred while sending
 * OTP to the admin/user.
 */
class OTPSendingFailedException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('ERROR_SENDING_OTP');
        $code         = 115;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
