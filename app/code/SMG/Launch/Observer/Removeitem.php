<?php
namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\Product;

class Removeitem implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;
	
	protected $_collectionFactory;
	
	protected $_productManager;

	public function __construct(
		\SMG\Launch\Model\Session $launchSession,
		 Session $checkoutSession,
		 CollectionFactory $collectionFactory,
		 Product $productManager,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $launchSession;
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
		$item = $observer->getQuoteItem();
		
		$product = $this->_productManager->load($item->getProductId());
		$candidates = array();
		$candidates['id'] = $item->getId();
		$candidates['name'] = $item->getName();
		$candidates['sku'] = $item->getSku();
		$candidates['quantity'] =  $item->getQty();
		$candidates['unitPrice'] = $item->getProduct()->getFinalPrice();
		$categoryIds = $product->getCategoryIds();
		$categories = $this->_collectionFactory->create()
							 ->addAttributeToSelect('*')
							 ->addAttributeToFilter('entity_id', $categoryIds);
		$cats = [];
		foreach ($categories as $category) {
			$cats[] = $category->getName();
		}					
		$candidates['category'] = implode(',',$cats);
		$this->_checkoutSession->setDeleteitem($candidates); 
		return $this;
	}
}