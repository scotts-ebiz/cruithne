<?php

namespace Plego\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class InitiateCheckout implements ObserverInterface {

	/** @var \Plego\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \Plego\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\Plego\Launch\Model\Session $fbPixelSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Plego\Launch\Helper\Data $helper
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