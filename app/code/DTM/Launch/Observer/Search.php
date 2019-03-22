<?php

namespace DTM\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class Search implements ObserverInterface {

	/** @var \DTM\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \DTM\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;
	/** @var \Magento\Search\Helper\Data $_searchHelper */
	protected $_searchHelper;

	public function __construct(
		\DTM\Launch\Model\Session $fbPixelSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\DTM\Launch\Helper\Data $helper,
		\Magento\Search\Helper\Data $searchHelper
	) {
		$this->_launchSession = $fbPixelSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
		$this->_searchHelper = $searchHelper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		$text = $this->_searchHelper->getEscapedQueryText();



		$data = [
			'search_string' => $text
		];

		$this->_launchSession->setSearch($data);

		return $this;
	}
}