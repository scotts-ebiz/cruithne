<?php
namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class Removeitem implements ObserverInterface {

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
		$item = $observer->getQuoteItem();
		
		$product = $objectManager->get('Magento\Catalog\Model\Product')->load($item->getProductId());
		$candidates = array();
		$candidates['id'] = $item->getId();
		$candidates['name'] = $item->getName();
		$candidates['sku'] = $item->getSku();
		$candidates['quantity'] =  $item->getQty();
		$candidates['unitPrice'] = $item->getProduct()->getFinalPrice();
		$categoryIds = $product->getCategoryIds();
		$categories = $categoryCollection->create()
							 ->addAttributeToSelect('*')
							 ->addAttributeToFilter('entity_id', $categoryIds);
		$cats = [];
		foreach ($categories as $category) {
			$cats[] = $category->getName();
		}					
		$candidates['category'] = implode(',',$cats);
		$CheckoutSession->setDeleteitem($candidates); 
		return $this;
	}
}