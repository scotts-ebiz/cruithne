<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class InitiateCheckout implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\SMG\Launch\Model\Session $fbPixelSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $fbPixelSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper         = $helper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {

		if (!count($this->_checkoutSession->getQuote()->getAllVisibleItems())) {
			return $this;
		}

		$this->_launchSession->setInitiateCheckout();

		return $this;
	}
}