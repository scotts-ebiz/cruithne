<?php

namespace SMG\Subscriptions\Observer;

use \Psr\Log\LoggerInterface;

class SaveOrder implements \Magento\Framework\Event\ObserverInterface
{

	protected $_logger;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
    )
    {        
        $this->_logger = $logger;
    }


	public function execute(\Magento\Framework\Event\Observer $observer) {
		$this->_logger->debug('aaa');
        $this->_logger->debug($_POST);
        $this->_logger->debug($this->getRequest()->getParams());
	}
}