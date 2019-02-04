<?php
namespace Freshrelevance\Digitaldatalayer\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Product extends AbstractHelper
{
    private $objectManager;
    private $store;
    private $config;
    private $configurableModel;
    private $productModel;
    private $stockInterface;
    private $categoryModel;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $store,
        \Freshrelevance\Digitaldatalayer\Helper\Config $config,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableModel,
        \Magento\Catalog\Model\ProductRepository $productModel,
        \Magento\CatalogInventory\Api\StockStateInterface $stockInterface,
        \Magento\Catalog\Model\Category $categoryModel,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    ) {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->store = $store->getStore();
        $this->config = $config;
        $this->configurableModel = $configurableModel;
        $this->productModel = $productModel;
        $this->stockInterface = $stockInterface;
        $this->categoryModel = $categoryModel;
        $this->reviewFactory = $reviewFactory;
    }

    public function getBundleSelectedLinkedProducts($item)
    {
        $linkedProducts=[];
        $product = $item->getProduct();

        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $product->getTypeInstance();

        // get bundle options
        $optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');
        $bundleOptionsIds = $optionsQuoteItemOption ? unserialize($optionsQuoteItemOption->getValue()) : [];
        if ($bundleOptionsIds) {
            /** @var \Magento\Bundle\Model\ResourceModel\Option\Collection $optionsCollection */
            $optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);

            // get and add bundle selections collection
            $selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');

            $bundleSelectionIds = unserialize($selectionsQuoteItemOption->getValue());

            if (!empty($bundleSelectionIds)) {
                $selectionsCollection = $typeInstance->getSelectionsByIds($bundleSelectionIds, $product);

                $bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
                foreach ($bundleOptions as $bundleOption) {
                    if ($bundleOption->getSelections()) {
                        $bundleSelections = $bundleOption->getSelections();
                        foreach ($bundleSelections as $bundleSelection) {
                            $linkedProducts[]=$this->getProductData($bundleSelection);
                        }
                    }
                }
            }
        }
        return $linkedProducts;
    }
    public function getBundleLinkedProducts($product)
    {
        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product),
            $product
        );
        $linkedProducts=[];
        foreach ($selectionCollection as $product) {
            $linkedProducts=$this->getProductData($product);
        }
        return $linkedProducts;
    }
    public function getConfigurableLinkedProducts($product)
    {
        $products=$this->configurableModel->getUsedProducts($product);
        $productsData=[];
        foreach ($products as $product) {
            $productsData[]=$this->getProductData($product);
        }
        return $productsData;
    }
    public function getConfigurableSelectedLinkedProducts($item)
    {
        $productModel = $this->productModel;
        $simpleProduct=$productModel->get($item->getSku());
        return $this->getProductData($simpleProduct);
    }
    public function getGroupedLinkedProducts($product)
    {
        $products = $product->getTypeInstance()->getAssociatedProducts($product);
        $productsData=[];
        foreach ($products as $product) {
            $productsData[]=$this->getProductData($product);
        }
        return $productsData;
    }
    public function getGroupedSelectedLinkedProducts($item)
    {
        $productModel = $this->productModel;
        $simpleProduct=$productModel->get($item->getSku());
        return $this->getProductData($simpleProduct);
    }
    public function getProductInfo($product)
    {
        $store=$this->store;
        $productInfo = [];
        $productInfo['productID'] = $product->getEntityId();
        $productInfo['productName'] = $product->getName();
        $productInfo['sku'] = $product->getSku();
        $productInfo['description'] = strip_tags($product->getData('description'));
        $productInfo['productURL'] = $product->setStoreId($store->getId())->getUrlInStore();
        if ($product->getImage() && $product->getImage() !== "no_selection") {
            $productInfo['productImage']=$store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).
            'catalog/product' . $product->getImage();
        }
        if ($product->getThumbnail() && $product->getImage() !== "no_selection") {
            $productInfo['productThumbnail'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).
            'catalog/product'.$product->getThumbnail();
        }

        foreach ($this->config->getEnabledProductAttributes() as $attr) {
            // Protect against blank and none in attr
            if ($attr && $attr !== 'none' && $product->getData($attr)) {
                $productInfo['attributes'][$attr] = $product->getData($attr);
            }
            if ($attr && $attr == 'all' && $product->getData()) {
                $productInfo['attributes'] = $product->getData();
                // Break to make sure we don't double populate any fields
                break;
            }
        }
        if ($this->config->getEnabledStockExposure() != 0) {
            $productInfo['stock'] = $this->getProductStockInfo($product, $this->config->getEnabledStockExposure());
        } else {
            // Remove stock exposure from attributes if it was exposed
            if (in_array('attributes', $productInfo) && in_array('quantity_and_stock_status', $productInfo['attributes'])) {
                unset($productInfo['attributes']['quantity_and_stock_status']);
            }

        }
        // Fetch Product Rating and Rating Count
        if ($this->config->getEnabledRatingExposure() != 0) {
            $prodRating = $this->getRatingSummary($product);
            if ($prodRating) {
                $productInfo['rating'] = $prodRating[0];
                $productInfo['ratingCount'] = $prodRating[1];
            } else {
                $productInfo['rating'] = null;
            }
        }
        return $productInfo;
    }

    public function getSelectionQty(\Magento\Catalog\Model\Product $product, $selectionId)
    {
        $selectionQty = $product->getCustomOption('selection_qty_' . $selectionId);
        if ($selectionQty) {
            return $selectionQty->getValue();
        }
        return 0;
    }
    public function getRatingSummary($product)
    {
        $rating = null;
        $ratingCount = null;

        try {
            $this->reviewFactory->create()->getEntitySummary($product, $this->store->getId());
            $rating = $product->getRatingSummary()->getRatingSummary();
            $ratingCount = $this->reviewFactory->create()->getTotalReviews($product->getEntityId(),false);
        } catch (\Exception $e) {}

        return array($rating, $ratingCount);
    }
    public function getProductStockInfo($product, $config)
    {
        $info = null;
        $StockState = $this->stockInterface;
        if ($config == 2) {
            $info = $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
        } else {
            $status = $product->getData('quantity_and_stock_status')['is_in_stock'];
            $info = $status == true ? 'in stock' : 'out of stock';
        }

        return $info;
    }
    public function getProductCategories($product)
    {
        $categoryModel = $this->categoryModel;
        $categories = [];
        $categoryIds = $product->getCategoryIds();
        $i = 0;
        foreach ($categoryIds as $categoryId) {
            if ($i == 1) {
                $categories['primaryCategory'] = $categoryModel->load($categoryId)->getName();
            } elseif ($i == 0) {
                $categories['subCategory1'] = $categoryModel->load($categoryId)->getName();
            } else {
                $categories['subCategory'.$i] = $categoryModel->load($categoryId)->getName();
            }
            $i++;
        }
        $categories['productType'] = $product->getTypeId();
        return $categories;
    }
    public function getProductData($product)
    {
        $productData=[];

        /* Categories */
        $categories = $this->getProductCategories($product);

        /* Price */
        $price = [];
        if ($product) {
            $priceObj = $product->getPriceInfo();
            if (isset($product->getData()['price'])) {
                $price['basePrice'] = $priceObj->getPrice('base_price')->getAmount()->getValue();
                $price['priceWithTax'] = $priceObj->getPrice('final_price')->getAmount()->getValue();
            } elseif ($priceObj) {
                // Likely a Configurable or Bundle Product, use minimal price
                try {
                    $finalPrice = $priceObj->getPrice('final_price');
                    $price['basePrice'] = $finalPrice->getMinimalPrice()->getValue('amount');
                    $price['priceWithTax'] = $finalPrice->getAmount()->getValue();
                } catch (\Exception $e) {
                }
            }
            if ($product->isSaleable()) {
                $price['regularPrice'] = $priceObj->getPrice('regular_price')->getAmount()->getValue();
            }
        }
        $price['currency']=$this->store->getCurrentCurrencyCode();

        $productData['productInfo']=$this->getProductInfo($product);
        $productData['category']=$categories;
        $productData['price']=$price;

        return $productData;
    }
}
