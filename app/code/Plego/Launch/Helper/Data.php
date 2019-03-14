<?php

namespace Plego\Launch\Helper;

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
	/** @var \Plego\Launch\Model\Session $_fbPixelSession */
	protected $_fbPixelSession;

	public function __construct(
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Helper\Context $context,
		\Plego\Launch\Model\Session $fbPixelSession
	) {
		$this->_checkoutSession = $checkoutSession;
		$this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $context->getScopeConfig();
		$this->_productRepository = $productRepository;
		$this->_storeManager = $storeManager;
		$this->_fbPixelSession = $fbPixelSession;

		parent::__construct( $context );
	}

	public function isPlegoEnabled($store)
	{
		return $this->_scopeConfig->getValue('plego_launch/adobe/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
	}
	public function isConversionPixelEnabled()
	{
		return true;
	}

	public function isAddToCartPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue("plego_launch/add_to_cart/enabled");
	}

	public function isAddToWishlistPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue('plego_launch/add_to_wishlist/enabled');
	}

	public function isInitiateCheckoutPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue('plego_launch/inititiate_checkout/enabled');
	}

	public function isViewProductPixelEnabled()
	{
		return $this->_scopeConfig->getValue('plego_launch/view_product/enabled');
	}

	public function isSearchPixelEnabled()
	{
		return true;//return $this->_scopeConfig->getValue('plego_launch/search/enabled');
	}

	public function getScript($store)
	{
		return $this->_scopeConfig->getValue("plego_launch/adobe/url", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
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
}