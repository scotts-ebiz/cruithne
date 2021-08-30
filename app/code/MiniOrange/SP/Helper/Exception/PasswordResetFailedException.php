<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that there was an Invalid Operation
 */
class PasswordResetFailedException extends \Exception
{
	public function __construct() 
	{
		$message 	= SPMessages::parse('ERROR_OCCURRED');
		$code 		= 116;		
        parent::__construct($message, $code, NULL);
    }

    public function __toString() 
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}