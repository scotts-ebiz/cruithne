<?php
namespace SMG\Iframes\Block ;

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
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
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

        $this->_skuFromUrl = $sku;

        $storeId = $this->_storeManager->getStore()->getId();
        $product = $this->productRepository->get($sku);

        if ($product === NULL) {
            return;
        }



        // TODO: bundles

//        $selectedProductId = $product->getId();
//
//        $childProductIds = $product->getTypeInstance(true)
//            ->getChildrenIds($selectedProductId);
//
//        if ($childProductIds != NULL) {
//            $childProductIds = $childProductIds[0];
//        }
//        else {
//            $childProductIds = [];
//        }
//
//        $childProducts = [];
//        $priceOfChildren = 0.0;
//        if ("bundle" == $product->getTypeId()) {
//            $optionCollection = $product->getTypeInstance()->getOptionsCollection();
//            $optionsIds = $product->getTypeInstance()->getOptionsIds();
//            $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($optionsIds);
//            $options = $optionCollection->appendSelections($selectionCollection);
//            foreach ($options as $option) {
//                $selections = $option->getSelections();
//                foreach ($selections as $selection) {
//                    $childPrice = $selection->getSelectionPriceValue();
//                    $childQty = $selection->getSelectionQty();
//                    $priceOfChildren += $childPrice * $childQty;
//                }
//            }
//        }
//        foreach( $childProductIds as $id ) {
//            $child = $this->productRepository->get( $id );
//            $childProducts[] = $child;
//        }

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
        }
        return $this->_coreRegistry->registry('product');
    }

    public function getBaseUrl() {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
