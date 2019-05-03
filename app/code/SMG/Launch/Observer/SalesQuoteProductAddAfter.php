<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\Product;

class SalesQuoteProductAddAfter implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;
	
	protected $_collectionFactory;
	
	protected $_productManager;

	public function __construct(
		\SMG\Launch\Model\Session $_launchSession,
		 Session $checkoutSession,
		 CollectionFactory $collectionFactory,
		 Product $productManager,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $_launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
		$this->_collectionFactory = $collectionFactory;
		$this->_productManager = $productManager;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		$items = $observer->getItems();
		$i = 0;
		foreach ($items as $item) {
			if ($item->getParentItem()) {
				continue;
			}
			$i++;
			$product = $this->_productManager->load($item->getProductId());
			$candidates[$i]['id'] = $item->getId();
			$candidates[$i]['name'] = $item->getName();
			$candidates[$i]['sku'] = $item->getSku();
			$candidates[$i]['quantity'] =  $item->getProduct()->getQty();
			$candidates[$i]['unitPrice'] = $item->getProduct()->getFinalPrice();
			$categoryIds = $product->getCategoryIds();
			$categories = $this->_collectionFactory->create()
                                 ->addAttributeToSelect('*')
                                 ->addAttributeToFilter('entity_id', $categoryIds);
			$cats = [];
			foreach ($categories as $category) {
				$cats[] = $category->getName();
			}					
			$candidates[$i]['category'] = implode(',',$cats);
		}
		$this->_checkoutSession->setDtmAddToCart($candidates); 
		return $this;
	}
}