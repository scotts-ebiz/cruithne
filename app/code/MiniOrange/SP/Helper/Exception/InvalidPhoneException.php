<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that user didnot provide a valid
 * Certificate for encrypted assertion from the SP.
 */
class InvalidPhoneException extends \Exception
{
	public function __construct($phone) 
	{
		$message 	= SPMessages::parse('ERROR_PHONE_FORMAT',array('phone'=>$phone));
		$code 		= 112;		
        parent::__construct($message, $code, NULL);
    }

    public function __toString() 
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}