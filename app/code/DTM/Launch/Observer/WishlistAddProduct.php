<?php

namespace DTM\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class WishlistAddProduct implements ObserverInterface {

	/** @var \DTM\Launch\Model\Session $_fbPixelSession */
	protected $_fbPixelSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \DTM\Launch\Helper\Data $_fbPixelHelper */
	protected $_fbPixelHelper;

	public function __construct(
		\DTM\Launch\Model\Session $fbPixelSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\DTM\Launch\Helper\Data $helper
	) {
		$this->_fbPixelSession = $fbPixelSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_fbPixelHelper         = $helper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		/** @var \Magento\Catalog\Model\Product $product */
		$product = $observer->getProduct();
		if (!$this->_fbPixelHelper->isAddToWishlistPixelEnabled() || !$product) {
			return $this;
		}

		$data = [
			'content_type' => 'product',
			'content_ids' => [$product->getSku()],
			'value' => $product->getFinalPrice(),
			'currency' => $this->_fbPixelHelper->getCurrencyCode()
		];

		$this->_fbPixelSession->setAddToWishlist($data);

		return $this;
	}
}