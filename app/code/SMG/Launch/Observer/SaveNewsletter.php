<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveNewsletter implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\SMG\Launch\Model\Session $launchSession,
		\Magento\Checkout\Model\Session $checkoutSession,
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
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$session = $objectManager->get('Magento\Catalog\Model\Session');
		$session->setDtmNewsletter((string)$observer->getEvent()->getSubscriber()->getSubscriberEmail()); 

		return $this;
	}
}