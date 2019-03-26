<?php

namespace DTM\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogControllerProductInitAfter implements ObserverInterface {

	/** @var \DTM\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \DTM\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\DTM\Launch\Model\Session $fbPixelSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\DTM\Launch\Helper\Data $helper
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
		/** @var Mage_Catalog_Model_Product $product */
		$product = $observer->getProduct();

		$data = [
			'content_type' => 'product',
			'content_ids' => [$product->getSku()],
			'value' => $product->getFinalPrice(),
			'currency' => $this->_launchHelper->getCurrencyCode(),
			'content_name' => $product->getName()
		];

		$this->_launchSession->setViewProduct($data);

		return $this;
	}
}