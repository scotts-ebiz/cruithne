<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class Updatecart implements ObserverInterface {

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
		$CheckoutSession = $objectManager->get('Magento\Checkout\Model\Session');
		$categoryCollection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
		
		$items = $observer->getCart()->getQuote()->getItems();
		$info = $observer->getInfo()->getData();
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
			$candidates[$i]['quantity'] =  $info[$item->getId()]['qty'];
			$candidates[$i]['previousQuantity'] =  $item->getQty();
			
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
		$CheckoutSession->setUpdateqty($candidates); 
		return $this;
	}
}