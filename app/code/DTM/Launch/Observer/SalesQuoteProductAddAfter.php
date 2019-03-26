<?php

namespace DTM\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteProductAddAfter implements ObserverInterface {

	/** @var \DTM\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \DTM\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\DTM\Launch\Model\Session $_launchSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\DTM\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $_launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		$items = $observer->getItems();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$CheckoutSession = $objectManager->get('Magento\Checkout\Model\Session');
		$categoryCollection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
		$i = 0;
		foreach ($items as $item) {
			if ($item->getParentItem()) {
				continue;
			}
			$i++;
			$product = $objectManager->get('Magento\Catalog\Model\Product')->load($item->getProductId());
			$candidates[$i]['id'] = $item->getId();
			$candidates[$i]['name'] = $item->getName();
			$candidates[$i]['sku'] = $item->getSku();
			$candidates[$i]['quantity'] =  $item->getProduct()->getQty();
			$candidates[$i]['unitPrice'] = $item->getProduct()->getFinalPrice();
			$categoryIds = $product->getCategoryIds();
			$categories = $categoryCollection->create()
                                 ->addAttributeToSelect('*')
                                 ->addAttributeToFilter('entity_id', $categoryIds);
			$cats = [];
			foreach ($categories as $category) {
				$cats[] = $category->getName();
			}					
			$candidates[$i]['category'] = implode(',',$cats);
		}
		$CheckoutSession->setDtmAddToCart($candidates); 
		return $this;
	}
}