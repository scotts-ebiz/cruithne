<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

// @codingStandardsIgnoreFile

namespace Firebear\ImportExport\Model\Import\Product;

use Magento\CatalogImportExport\Model\Import\Product\Option as BaseOption;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Entity class which provide possibility to import product custom options
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Option extends BaseOption
{
    public function __construct(
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $colIteratorFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Firebear\ImportExport\Model\Source\Factory $factory,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        array $data = []
    ) {
        parent::__construct(
            $importData,
            $resource,
            $resourceHelper,
            $storeManager,
            $productFactory,
            $optionColFactory,
            $colIteratorFactory,
            $catalogData,
            $scopeConfig,
            $dateTime,
            $errorAggregator,
            $data
        );
        $this->factory = $factory;
        $this->_dataSourceModel = $importFireData;
    }
}
