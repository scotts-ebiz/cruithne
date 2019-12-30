<?php

namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Updatecart implements ObserverInterface {

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
		
		$items = $observer->getCart()->getQuote()->getItems();
		$info = $observer->getInfo()->getData();
		$i = 0;
		$candidates = array();

		foreach ($items as $item) {
            $product = $item->getProduct();
            $option = null;
            // For configurable products we need the selected option
            if($item->getProductType() === "configurable") {
                $productOptions = $product->getTypeInstance(true)->getOrderOptions($product);
                $selectedOptionId = $productOptions['info_buyRequest']['selected_configurable_option'];
                $option = $this->_productRepository->getById($selectedOptionId);
            }

			$i++;
			$candidates[$i]['id'] = $item->getProductId();
			$candidates[$i]['name'] = $item->getName();
			$candidates[$i]['sku'] = $item->getSku();
            // Product may or may not have a drupal id, need a product from the repo to see it tho
            $productFromRepo = $this->_productRepository->getById($item->getProductId());
            if (!empty($productFromRepo->getData('drupalproductid'))) {
                $candidates[$i]['drupalproductid'] = $productFromRepo->getData('drupalproductid');
            }
            //If we have a configurable option, we override certain properties
            if (!empty($option) && !empty($option->getData('drupalproductid'))) {
                $candidates[$i]['id'] = $option->getId();
                $candidates[$i]['drupalproductid'] = $option->getData('drupalproductid');
            }
			$candidates[$i]['quantity'] =  $info[$item->getId()]['qty'];
			$candidates[$i]['previousQuantity'] =  $item->getQty();
			$candidates[$i]['unitPrice'] = $product->getFinalPrice();
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
		$this->_checkoutSession->setUpdateqty($candidates);
	}
}