<?php

namespace Plego\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class WishlistAddProduct implements ObserverInterface {

	/** @var \Plego\Launch\Model\Session $_fbPixelSession */
	protected $_fbPixelSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \Plego\Launch\Helper\Data $_fbPixelHelper */
	protected $_fbPixelHelper;

	public function __construct(
		\Plego\Launch\Model\Session $fbPixelSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Plego\Launch\Helper\Data $helper
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