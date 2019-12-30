<?php
namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Removeitem implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;
	
	protected $_collectionFactory;
	
	protected $_productRepository;

	public function __construct(
		\SMG\Launch\Model\Session $launchSession,
		 Session $checkoutSession,
		 CollectionFactory $collectionFactory,
		 ProductRepositoryInterface $productRepository,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
		$this->_collectionFactory = $collectionFactory;
		$this->_productRepository = $productRepository;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
        $item = $observer->getQuoteItem();
        $product = $item->getProduct();
        // For configurable products we need the selected option
        if($item->getProductType() === "configurable") {
            $productOptions = $product->getTypeInstance(true)->getOrderOptions($product);
            $selectedOptionId = $productOptions['info_buyRequest']['selected_configurable_option'];
            $option = $this->_productRepository->getById($selectedOptionId);
        }

        $candidates = array();
		$candidates['id'] = $item->getProductId();
		$candidates['name'] = $item->getName();
		$candidates['sku'] = $item->getSku();
		$candidates['quantity'] =  $item->getQty();
		$candidates['unitPrice'] = $product->getFinalPrice();
		// Product may or may not have a drupal id, need product from repo to see it tho
        $productFromRepo = $this->_productRepository->getById($item->getProductId());
		if (!empty($productFromRepo->getData('drupalproductid'))) {
            $candidates['drupalproductid'] = $productFromRepo->getData('drupalproductid');
        }
		//If we have a configurable option, we override certain properties
        if (!empty($option) && !empty($option->getData('drupalproductid'))) {
            $candidates['id'] = $option->getId();
            $candidates['drupalproductid'] = $option->getData('drupalproductid');
        }
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
	}
}