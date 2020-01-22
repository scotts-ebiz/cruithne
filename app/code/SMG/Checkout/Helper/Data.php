<?php

namespace SMG\Checkout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;


class Data extends AbstractHelper{

	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var \Magento\Sales\Model\OrderFactory $_orderFactory */
	protected $_orderFactory;
	/** @var ScopeConfigInterface $_scopeConfig */
	protected $_scopeConfig;
	/** @var \Magento\Sales\Model\Order $_order */
	protected $_order;
	/** @var \Magento\Catalog\Model\ProductRepository $_productRepository */
	protected $_productRepository;
	/** @var \Magento\Store\Model\StoreManagerInterface $_storeManager */
	protected $_storeManager;
	
	protected $_BundleProductType;
	
	protected $_Product;

	public function __construct(
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Bundle\Model\Product\Type $BundleProductType,
		\Magento\Catalog\Model\Product $Product
	) {
		$this->_checkoutSession = $checkoutSession;
		$this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $context->getScopeConfig();
		$this->_productRepository = $productRepository;
		$this->_storeManager = $storeManager;
		$this->_BundleProductType = $BundleProductType;
		$this->_Product = $Product;

		parent::__construct( $context );
	}

	public function getBundleProductType(){
		return $this->_BundleProductType;
	}
	public function getProduct(){
		return $this->_Product;
	}
}