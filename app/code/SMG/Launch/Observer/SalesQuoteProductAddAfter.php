<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class SalesQuoteProductAddAfter implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;
	
	protected $_collectionFactory;

	public function __construct(
		\SMG\Launch\Model\Session $_launchSession,
		 Session $checkoutSession,
		 CollectionFactory $collectionFactory,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $_launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
		$this->_collectionFactory = $collectionFactory;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		$items = $observer->getItems();
		$i = 0;
        $candidates = array();
		foreach ($items as $item) {
            // If there is a child product with a parent that is not configurable, we do not want to include it.
            if($item->getParentItem()){
                if (!$item->getParentItem()->getProductType() === "configurable") {
                    continue;
                }
            }
            // If there is a configurable product, we don't want to include it,
            // but we will include its children because of the exception we made above
            if ($item->getProductType() === "configurable") {
                continue;
            }

            $product = $item->getProduct();

			$i++;
			$candidates[$i]['id'] = $product->getId();
			$candidates[$i]['name'] = $item->getName();
			$candidates[$i]['sku'] = $item->getSku();
			$candidates[$i]['quantity'] =  $item->getQty();
			$candidates[$i]['unitPrice'] = $product->getFinalPrice();
			if ($product->getData('drupalproductid')) {
                $candidates[$i]['drupalproductid'] = $product->getData('drupalproductid');
            }
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
	}
}