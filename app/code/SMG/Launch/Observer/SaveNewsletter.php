<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;

class SaveNewsletter implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\SMG\Launch\Model\Session $launchSession,
		 Session $checkoutSession,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		
		$this->_checkoutSession->setDtmNewsletter((string)$observer->getEvent()->getSubscriber()->getSubscriberEmail()); 

		return $this;
	}
}