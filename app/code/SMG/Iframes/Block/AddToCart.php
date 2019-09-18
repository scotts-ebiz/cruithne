<?php
namespace SMG\Iframes\Block ;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface as jsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;
use \SMG\Iframes\Model\ContentSecurityPolicy;
use \Magento\Catalog\Block\Product\View;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;

class AddToCart extends View
{
    protected $_contentSecurityPolicy;
    protected $_skuFromUrl;
    protected $_storeManager;

    public function __construct(
        Context $context,
        EncoderInterface $urlEncoder,
        jsonEncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        array $data = [],
        ContentSecurityPolicy $contentSecurityPolicy) {


        parent::__construct($context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency);
        $this->_contentSecurityPolicy = $contentSecurityPolicy;
        $this->_storeManager = $storeManager;
        $this->_contentSecurityPolicy->setContentSecurityPolicy();


        $sku = $this->getRequest()->getParam('sku');
        $qty = $this->getRequest()->getParam('quantity',1);
        $desktop = $this->getRequest()->getParam('desktop', false);
        $storeId = $this->_storeManager->getStore()->getId();

        $this->_skuFromUrl = $sku;
        $product = $this->getProduct();

        if ($product === NULL) {
            return;
        }

        if( $product ) {
            $this->setData("store_id", $storeId)
                ->setData("product_id", $product->getId())
                ->setData("selected_product", $product)
                //->setData("child_products", $childProducts)
                ->setData("child_products", null)
                ->setData("quantity", $qty)
                //->setData("children_price", $priceOfChildren)
                ->setData("children_price", null)
                ->setData("base_price", $product->getData("price"))
                ->setData("base_product_id", $product->getId())
                ->setData("sku", $product->getData("sku"))
                ->setData("drupalProductId", $product->getData("drupalproductid"))
                ->setData("desktop", $desktop);
        }

    }

    /**
     * Retrieve current product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (!$this->_coreRegistry->registry('product') && $this->_skuFromUrl) {
            $product = $this->productRepository->get($this->_skuFromUrl);
            $this->_coreRegistry->register('product', $product);
            $this->_coreRegistry->register('current_product', $product);
        }

        return $this->_coreRegistry->registry('product');
    }

    public function getBaseUrl() {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
