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
use \Magento\Catalog\Block\Product\View;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class AddToCart extends View
{
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
        array $data = []) {


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
        $this->_storeManager = $storeManager;

        $sku = $this->getRequest()->getParam('sku');

        $this->_skuFromUrl = $sku;
        $product = $this->getProduct();

        if ($product === NULL) {
            return;
        }

        $skusById = array();
        $drupalIdsById = array();
        $skusById[$product->getID()] = $product->getData("sku");
        $drupalIdsById[$product->getID()] = $product->getData("drupalproductid");

        if($product->getTypeId() == Configurable::TYPE_CODE){
            $children = $product->getTypeInstance()->getUsedProducts($product);

            foreach ($children as $child){
                $skusById[$child->getID()] = $child->getData("sku");
                $drupalIdsById[$child->getID()] = $child->getData("drupalproductid");
            }
        }

        if( $product ) {
            $this->setData("skusById", $skusById)
                ->setData("drupalIdsById", $drupalIdsById);
        }

    }

    public function getBaseUrl() {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
