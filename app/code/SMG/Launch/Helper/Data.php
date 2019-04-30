<?php

namespace SMG\Launch\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;


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
	/** @var \SMG\Launch\Model\Session $_fbPixelSession */
	protected $_fbPixelSession;
	
	protected $_BundleProductType;
	
	protected $_Product;
	
	protected $_StoremanagerInterface;
	
	protected $_CategoryCollection;
	
	protected $_CustomerSession;
	
	protected $_CheckoutCart;
	
	protected $_ActionContext;
	
	protected $_Registry;
	
	protected $_CatalogCategory;
	
	protected $_CatalogSession;

	public function __construct(
		\Magento\Checkout\Model\Session $CheckoutSession,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Catalog\Model\ProductRepository $ProductRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Bundle\Model\Product\Type $BundleProductType,
		\Magento\Catalog\Model\Product $Product,
		\Magento\Store\Model\StoreManagerInterface $StoremanagerInterface,
		\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $CategoryCollection,
		\Magento\Customer\Model\Session $CustomerSession,
		\Magento\Checkout\Model\Cart $CheckoutCart,
		\Magento\Framework\App\Action\Context $ActionContext,
		\Magento\Framework\Registry $Registry,
		\Magento\Catalog\Model\Category $CatalogCategory,
		\Magento\Catalog\Model\Session $CatalogSession,
		\SMG\Launch\Model\Session $fbPixelSession
	) {
		$this->_checkoutSession = $CheckoutSession;
		$this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $context->getScopeConfig();
		$this->_productRepository = $ProductRepository;
		$this->_storeManager = $storeManager;
		$this->_BundleProductType = $BundleProductType;
		$this->_Product = $Product;
		$this->_StoremanagerInterface = $StoremanagerInterface;
		$this->_CategoryCollection = $CategoryCollection;
		$this->_CustomerSession = $CustomerSession;
		$this->_CheckoutCart = $CheckoutCart;
		$this->_ActionContext = $ActionContext;
		$this->_Registry = $Registry;
		$this->_CatalogCategory = $CatalogCategory;
		$this->_CatalogSession = $CatalogSession;
		$this->_fbPixelSession = $fbPixelSession;

		parent::__construct( $context );
	}

	public function isSmgEnabled($store)
	{
		return $this->_scopeConfig->getValue('smg_launch/adobe/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
	}
	public function isConversionPixelEnabled()
	{
		return true;
	}

	public function isAddToCartPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue("smg_launch/add_to_cart/enabled");
	}

	public function isAddToWishlistPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue('smg_launch/add_to_wishlist/enabled');
	}

	public function isInitiateCheckoutPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue('smg_launch/inititiate_checkout/enabled');
	}

	public function isViewProductPixelEnabled()
	{
		return $this->_scopeConfig->getValue('smg_launch/view_product/enabled');
	}

	public function isSearchPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue('smg_launch/search/enabled');
	}

	public function getScript($store)
	{
		return $this->_scopeConfig->getValue("smg_launch/adobe/url", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
	}

	/**
	 * @param $event
	 * @param $data
	 * @return string
	 */
	public function getPixelHtml($event, $data = false)
	{
		$json = '';
		if ($data) {
			$json = ', ' . json_encode($data);
		}
		$html = <<<HTML
    <!-- Begin Adobe  {$event} Launch -->
    <script type="text/javascript">

    </script>
    <!-- End Adobe {$event} Launch -->
HTML;
		return $html;
	}

	public function getOrderIDs()
	{
		$orderIDs = array();

		/** @var \Magento\Sales\Model\Order\Item $item */
		foreach($this->getOrder()->getAllVisibleItems() as $item){
			$product = $this->_productRepository->getById($item->getProductId());
			$orderIDs = array_merge($orderIDs, $this->_getProductTrackID($product));
		}

		return json_encode(array_unique($orderIDs));
	}

	public function getOrder(){
		if(!$this->_order){
			$this->_order = $this->_checkoutSession->getLastRealOrder();
		}

		return $this->_order;
	}

	protected function _getProductTrackID($product)
	{
		$productType = $product->getTypeID();

		if($productType == "grouped") {
			return $this->_getProductIDs($product);
		} else {
			return $this->_getProductID($product);
		}
	}

	protected function _getProductIDs($product)
	{
		/** @var \Magento\Catalog\Model\Product $product */
		$group = $product->getTypeInstance()->setProduct($product);
		/** @var \Magento\GroupedProduct\Model\Product\Type\Grouped $group */
		$group_collection = $group->getAssociatedProductCollection($product);
		$ids = array();

		foreach ($group_collection as $group_product) {

			$ids[] = $this->_getProductID($group_product);
		}

		return $ids;
	}

	protected function _getProductID($product)
	{
		return array(
			$product->getSku()
		);
	}

	public function getOrderItemsCount()
	{
		$order = $this->getOrder();
		$qty = 0;
		/** @var \Magento\Sales\Model\Order\Item $item */
		foreach($order->getAllVisibleItems() as $item) {
			// Get a whole number
			$qty += round($item->getQtyOrdered());
		}
		return $qty;
	}

	public function getCurrencyCode(){
		return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
	}

	public function getSession(){
		return $this->_fbPixelSession;
	}
	public function getProduct(){
		return $this->_Product;
	}
	public function getStoremangerInterface(){
		return $this->_StoremanagerInterface;
	}
	public function getCategoryCollection(){
		return $this->_CategoryCollection;
	}
	public function getCustomerSession(){
		return $this->_CustomerSession;
	}
	public function getCheckoutCart(){
		return $this->_CheckoutCart;
	}
	public function getActionContext(){
		return $this->_ActionContext;
	}
	public function getRegistry(){
		return $this->_Registry;
	}
	public function getCatalogCategory(){
		return $this->_CatalogCategory;
	}
	public function getCheckoutSession(){
		return $this->_checkoutSession;
	}
	public function getProductRepository(){
		return $this->_productRepository;
	}
	public function getCatalogSession(){
		return $this->_CatalogSession;
	}
}