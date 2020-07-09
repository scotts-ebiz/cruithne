<?php

namespace SMG\Api\Helper;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Exception\LocalizedException;

class ProductExportHelper
{
    /**
     * @var JoinProcessorInterface
     */
    protected $_extensionAttributesJoinProcessor;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * ProductsHelper constructor.
     *
     * @param JoinProcessorInterface $joinProcessor
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        JoinProcessorInterface $joinProcessor,
        CollectionFactory $collectionFactory
    ) {
        $this->_extensionAttributesJoinProcessor = $joinProcessor;
        $this->_collectionFactory = $collectionFactory;
    }

    public function getProductInfo()
    {
        /** @var Collection $collection */
        $collection = $this->_collectionFactory->create();
        try {
            $collection->addAttributeToSelect('sku');
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('drupalproductid');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->load();
        } catch (LocalizedException $e) {
        }
        $rawArray = $collection->exportToArray();

        return $this->format($rawArray);
    }

    private function format($products)
    {
        foreach ($products as $key => $product) {
            unset($products[$key]['entity_id']);
            unset($products[$key]['attribute_set_id']);
            unset($products[$key]['has_options']);
            unset($products[$key]['required_options']);
            unset($products[$key]['created_at']);
            unset($products[$key]['updated_at']);
            unset($products[$key]['row_id']);
            unset($products[$key]['created_in']);
            unset($products[$key]['updated_in']);
            unset($products[$key]['store_id']);

            if ($products[$key]['status'] == Status::STATUS_ENABLED) {
                $products[$key]['enabled'] = true;
            } else {
                $products[$key]['enabled'] = false;
            }
            unset($products[$key]['status']);

            if (!empty($products[$key]['drupalproductid'])) {
                $products[$key]['drupalId'] = $products[$key]['drupalproductid'];
                unset($products[$key]['drupalproductid']);
            } else {
                $products[$key]['drupalId'] = 'NA';
            }

            // Add configurable sku if applicable
            if (!empty($products[$key]['type_id'] == 'configurable')) {
                $products[$key]['configurable_sku'] = $products[$key]['sku'];
                $products[$key]['sku'] = 'NA';
            } else {
                $products[$key]['configurable_sku'] = 'NA';
            }
            unset($products[$key]['type_id']);
        }

        return $products;
    }
}