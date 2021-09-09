<?php

namespace MiniOrange\SP\Helper;

/**
 * This class has been created so that we can override the default 
 * behaviour of Magento where it sets verifyPeer and verifyHost 
 * as true and create a new function which processes the 
 * response coming so that headers can be removed.
 * 
 * Keep veriyPeer as false at all times and verifyHost true 
 * when pushing to production and false when testing against
 * the test environment.
 */
class MoCurl extends \Magento\Framework\HTTP\Adapter\Curl
{
	protected $_header;
	protected $_body;
	
	public function __construct()
    {
       	$this->_config['verifypeer'] = false;
		$this->_config['verifyhost'] = false;
		$this->_config['header'] = false;
	}
}