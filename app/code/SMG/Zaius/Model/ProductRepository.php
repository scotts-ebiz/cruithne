<?php

namespace SMG\Zaius\Model;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;

class ProductRepository extends \Zaius\Engage\Model\ProductRepository
{
    private $_productConfigurable;
    protected $_stockRegistry;
    protected $_productHelper;
    protected $_storeManager;
    protected $_productFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductInterfaceFactory $productFactory,
        StockRegistryInterface $stockRegistry,
        Configurable $productConfigurable,
        ProductHelper $productHelper,
        Data $helper,
        Logger $logger
    ) {
        $this->_storeManager = $storeManager;
        $this->_stockRegistry = $stockRegistry;
        $this->_productFactory = $productFactory;
        $this->_productConfigurable = $productConfigurable;
        $this->_productHelper = $productHelper;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    public function getProductEventData($event, $product)
    {
        $productId = $this->_helper->getProductId($product);

        $productData = [
            'product_id' => $productId,
            'name' => $product->getName(),
            'brand' => $product->getData('brand'),
            'sku' => $product->getSku(),
            'upc' => $product->getData('upc'),
            'description' => $product->getData('short_description'),
            'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
            'price' => trim($product->getPrice()),
            'image_url' => $this->_productHelper->getImageUrl($product),
            'url_key' => $product->getData('url_key'),
        ];

        $parentProductId = $this->_productConfigurable->getParentIdsByChild($productId);

        if (isset($parentProductId[0])) {
            $productData['parent_product_id'] = $parentProductId[0];
        }

        if ($product->getData('special_price')) {
            $productData['special_price'] = trim($product->getData('special_price'));
            $productData['special_price_from_date'] = strtotime($product->getData('special_from_date')) ?: null;
            $productData['special_price_to_date'] = strtotime($product->getData('special_to_date')) ?: null;
        }
        $stockItem = $this->_stockRegistry->getStockItem($product->getId());
        if ($stockItem && $stockItem->getId() && $stockItem->getManageStock()) {
            $productData['qty'] = $stockItem->getQty();
            $productData['is_in_stock'] = $stockItem->getIsInStock();
        }
        foreach ($this->_getExtraProductAttributes() as $attributeCode => $attribute) {
            if ($attributeCode !== 'custom_layout_update_file') {
                $productData[$attributeCode] = $attribute->getFrontend()->getValue($product);
            }
        }
        $productData['price'] = preg_replace('/\s+/', '', $productData['price']);
        $productData['zaius_engage_version'] = $this->_helper->getVersion();
        if (!$product->getImage()) {
            $this->_logger->error('ZAIUS: Unable to retrieve product image_url');
        }
        $productData += $this->_helper->getDataSourceFields();
        $this->_logger->info("Event: $event");
        return $productData;
    }


}
