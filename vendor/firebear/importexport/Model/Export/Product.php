<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Magento\Framework\App\ObjectManager;
use \Magento\Store\Model\Store;
use \Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import;

class Product extends \Magento\CatalogImportExport\Model\Export\Product
{
    use \Firebear\ImportExport\Traits\Export\Products;

    use \Firebear\ImportExport\Traits\General;

    protected $headColumns;

    protected $additional;

    private $userDefinedAttributes = [];

    protected $keysAdditional;

    /** @var \Magento\Framework\App\ObjectManager  */
    protected $moduleManager;

    /**
     * Product constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory
     * @param \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer
     * @param Product\Additional $additional
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $dateAttrCodes
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
        \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory,
        \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
        \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer,
        Product\Additional $additional,
        \Magento\Framework\Module\Manager $moduleManager,
        array $dateAttrCodes = []
    ) {
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes
        );
        $this->additional = $additional;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param $productIds
     * @return array
     */
    protected function getMageStoreInventoryStocks($productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        $warehouseData = ObjectManager::getInstance()->get(\Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface::class);
        $warehouseData = $warehouseData->getStocksWarehouses($productIds)->getData();
        $stockItemRows = [];
        foreach ($warehouseData as $stockItemRow) {
            $productId = $stockItemRow['product_id'];
            unset(
                $stockItemRow['item_id'],
                $stockItemRow['product_id'],
                $stockItemRow['low_stock_date'],
                $stockItemRow['stock_id'],
                $stockItemRow['stock_status_changed_auto']
            );
            $stockItemRows[$productId] = $stockItemRow;
        }
        return $stockItemRows;
    }

    /**
     * @return array
     */
    protected function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds = array_keys($rawData);
            if ($this->moduleManager->isEnabled('MageArray_MaMarketPlace')) {
                /** @var \MageArray\MaMarketPlace\Helper\Data $mageArrayHelper */
                $mageArrayHelper = ObjectManager::getInstance()->get(\MageArray\MaMarketPlace\Helper\Data::class);
            }

            if ($this->moduleManager->isEnabled('Webkul_Marketplace')) {
                /** @var \Webkul\Marketplace\Helper\Data $webKulHelperManager */
                $webKulHelperManager = ObjectManager::getInstance()->get(\Webkul\Marketplace\Helper\Data::class);
            }

            if ($this->moduleManager->isEnabled('Magestore_InventorySuccess')) {
                $stockItemRows = $this->getMageStoreInventoryStocks($productIds);
            } else {
                $stockItemRows = $this->prepareCatalogInventory($productIds);
            }

            $this->rowCustomizer->prepareData(
                $this->_prepareEntityCollection($this->_entityCollectionFactory->create()),
                $productIds
            );

            $this->setAddHeaderColumns($stockItemRows);
            $prevData = [];
            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if (isset($stockItemRows[$productId])) {
                        $dataRow = array_merge($dataRow, $stockItemRows[$productId]);
                    }
                    if ($this->moduleManager->isEnabled('MageArray_MaMarketPlace')) {
                        $dataRow['vendor_id'] = $mageArrayHelper->getVendorByProductId($productId)->getUserId();
                    }
                    if ($this->moduleManager->isEnabled('Webkul_Marketplace')) {
                        $dataRow['vendor_id'] = $webKulHelperManager->getSellerProductDataByProductId($productId)
                            ->getFirstItem()->getId();
                    }
                    $this->appendMultirowData($dataRow, $multirawData);

                    if ($dataRow) {
                        if (!empty($prevData)) {
                            if (isset($prevData['sku']) && isset($dataRow['sku'])) {
                                if ($prevData['sku'] == $dataRow['sku']) {
                                    $dataRow = array_merge($prevData, $dataRow);
                                }
                            }
                        }
                        $exportData[] = $dataRow;
                    }
                    $prevData = $dataRow;
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData, 'product_id');

        $this->addHeaderColumns();
        $this->_headerColumns = $this->changeHeaders($this->_headerColumns);

        return $newData;
    }

    protected function _customHeadersMapping($rowData)
    {
        $rowData = parent::_customHeadersMapping($rowData);

        return ($this->_parameters['all_fields']) ? $this->_headerColumns : array_unique($rowData);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function export()
    {
        $this->keysAdditional = [];
        $this->scopeHeader();

        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $counts = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            if (isset($this->_parameters['last_entity_id'])
                && $this->_parameters['last_entity_id'] > 0
                && $this->_parameters['enable_last_entity_id'] > 0
            ) {
                $entityCollection->addFieldToFilter(
                    'entity_id', ['gt' => $this->_parameters['last_entity_id']]
                );
            }
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                if ($this->_parameters['enable_last_entity_id'] > 0) {
                    $this->lastEntityId = $dataRow['product_id'];
                }
                $writer->writeRow($this->_customFieldsMapping($dataRow));
                $counts++;
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return [$writer->getContents(), $counts, $this->lastEntityId];
    }

    protected function scopeHeader()
    {
        $page = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }

            $this->getHeaderData();
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        $this->headColumns = $this->_headerColumns;

        $this->_headerColumns = [];
    }

    protected function getHeaderData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            // $multirawData = $this->collectMultirawData();
            $productIds = array_keys($rawData);
            $stockItemRows = $this->prepareCatalogInventory($productIds);
            $this->rowCustomizer->prepareData($this->_getEntityCollection(), $productIds);
            //   $this->setHeaderColumns($multirawData['customOptionsData'], $stockItemRows);
            $this->setAddHeaderColumns($stockItemRows);

            $this->_headerColumns = $this->rowCustomizer->addHeaderColumns($this->_headerColumns);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function _customFieldsMapping($rowData)
    {

        $headerColumns = $this->_getHeaderColumns();

        $rowData = parent::_customFieldsMapping($rowData);
        if (count($headerColumns) != count(array_keys($rowData))) {
            $newData = [];
            foreach ($headerColumns as $code) {
                //   $flipFieldsMap = array_flip($this->_fieldsMap);
                $fieldCode = isset($this->_fieldsMap[$code]) ? $this->_fieldsMap[$code] : null;
                if ($fieldCode && isset($rowData[$fieldCode])) {
                    $newData[$code] = $rowData[$fieldCode];
                } else {
                    if (!isset($rowData[$code])) {
                        $newData[$code] = '';
                    } else {
                        $newData[$code] = $rowData[$code];
                    }
                }
            }
            $rowData = $newData;
        }

        return $rowData;
    }

    /**
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected function _prepareEntityCollection(\Magento\Eav\Model\Entity\Collection\AbstractCollection $collection)
    {
        if (!isset($this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP])
            || !is_array($this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP];
        }

        $collection = \Magento\ImportExport\Model\Export\Entity\AbstractEntity::_prepareEntityCollection($collection);
        $joinTable = 0;

        foreach ($this->additional->fields as $field) {
            if (isset($exportFilter[$field]) && !empty($exportFilter[$field])) {
                if ($field == 'store') {
                    $collection->addStoreFilter($exportFilter['store']);
                } else {
                    $collection->getSelect()->where(
                        $this->additional->convertFields($field) . "=?",
                        $exportFilter[$field]
                    );
                }
            }
        }

        if (isset($exportFilter['category_ids']) && !empty($exportFilter['category_ids'])) {
            $table = $collection->getResource()->getTable('catalog_category_product');
            $collection->joinTable(
                ['cp' => $table],
                'product_id = entity_id',
                ['category_id'],
                '`cp`.`category_id` IN (' . implode(",", $exportFilter['category_ids']) . ')'
            );
        }

        return $collection;
    }

    protected function collectMultirawData()
    {
        $data = [];
        $productIds = [];
        $rowWebsites = [];
        $rowCategories = [];
        $productLinkIds = [];

        $entityCollection = $this->_getEntityCollection(true);
        $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
        $entityCollection->addCategoryIds()->addWebsiteNamesToResult();
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($entityCollection as $item) {
            $productLinkIds[] = $item->getData($this->getProductEntityLinkField());
            $productIds[] = $item->getId();
            $rowWebsites[$item->getId()] = array_intersect(
                array_keys($this->_websiteIdToCode),
                $item->getWebsites()
            );
            $rowCategories[$item->getId()] = array_combine($item->getCategoryIds(), $item->getCategoryIds());
        }
        $entityCollection->clear();

        $categoryIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
        $categoryIds = array_combine($categoryIds, $categoryIds);
        foreach ($rowCategories as &$categories) {
            $categories = array_intersect_key($categories, $categoryIds);
        }

        $data['rowWebsites'] = $rowWebsites;
        $data['rowCategories'] = $rowCategories;

        $data['linksRows'] = $this->prepareLinks($productLinkIds);

        $data['customOptionsData'] = $this->getCustomOptionsData($productLinkIds);

        return $data;
    }

    /**
     * @return array
     */
    protected function fieldsCatalogInventory()
    {
        $fields = $this->_connection->describeTable($this->_itemFactory->create()->getMainTable());
        $rows = [];
        $row = [];
        unset(
            $fields['item_id'],
            $fields['product_id'],
            $fields['low_stock_date'],
            $fields['stock_id'],
            $fields['stock_status_changed_auto']
        );
        foreach ($fields as $key => $field) {
            $row[$key] = $key;
        }

        $rows[] = $row;
        return $rows;
    }

    protected function collectRawData()
    {
        $data = [];
        $items = $this->fireloadCollection();
        foreach ($items as $itemId => $itemByStore) {

            /**
             * @var int $itemId
             * @var ProductEntity $item
             */
            foreach ($this->getStores() as $storeId => $storeCode) {
                if (!isset($itemByStore[$storeId])) {
                    continue;
                }
                $item = $itemByStore[$storeId];
                $addtionalFields = [];
                $additionalAttributes = [];
                $productLinkId = $item->getData($this->getProductEntityLinkField());
                foreach ($this->_getExportAttrCodes() as $attrCodes) {
                    $attrValue = $item->getData($attrCodes);
                    $attrValue = str_replace(array("\r\n", "\n\r", "\n", "\r"), '', $attrValue);
                    if (!$this->isValidAttributeValue($attrCodes, $attrValue)) {
                        continue;
                    }

                    if (isset($this->_attributeValues[$attrCodes][$attrValue])
                        && !empty($this->_attributeValues[$attrCodes])
                    ) {
                        $attrValue = $this->_attributeValues[$attrCodes][$attrValue];
                    }
                    $fieldName = isset($this->_fieldsMap[$attrCodes]) ? $this->_fieldsMap[$attrCodes] : $attrCodes;

                    if ($this->_attributeTypes[$attrCodes] == 'datetime') {
                        if (in_array($attrCodes, $this->dateAttrCodes) || in_array($attrCodes, $this->userDefinedAttributes)) {
                            $attrValue = $this->_localeDate
                                ->formatDateTime(
                                    new \DateTime($attrValue),
                                    \IntlDateFormatter::SHORT,
                                    \IntlDateFormatter::NONE,
                                    null,
                                    date_default_timezone_get()
                                );
                        } else {
                            $attrValue = $this->_localeDate
                                ->formatDateTime(
                                    new \DateTime($attrValue),
                                    \IntlDateFormatter::SHORT,
                                    \IntlDateFormatter::SHORT
                                );
                        }
                    }

                    if ($storeId != Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$fieldName])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$fieldName] == htmlspecialchars_decode($attrValue)
                    ) { continue; }

                    if ($this->_attributeTypes[$attrCodes] !== 'multiselect') {
                        if (is_scalar($attrValue)) {
                            if (!in_array($fieldName, $this->_getExportMainAttrCodes())) {
                                $additionalAttributes[$fieldName] = $fieldName .
                                    ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $this->wrapValue($attrValue);
                                if ($this->checkDivideofAttributes()) {
                                    $addtionalFields[$fieldName] = $attrValue;
                                    if (!in_array($fieldName, $this->keysAdditional)) {
                                        $this->keysAdditional[] = $fieldName;
                                    }
                                }
                            }
                            //if (in_array($fieldName, ['description', 'short_description'])) {
                              //  $attrValue = addslashes($attrValue);
                            //}
                            $data[$itemId][$storeId][$fieldName] = htmlspecialchars_decode($attrValue);
                        }
                    } else {
                        $this->collectMultiselectValues($item, $attrCodes, $storeId);
                        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes])) {
                            $additionalAttributes[$attrCodes] = $fieldName .
                                ImportProduct::PAIR_NAME_VALUE_SEPARATOR . implode(
                                    ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                                    $this->wrapValue($this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes])
                                );
                            if ($this->checkDivideofAttributes()) {
                                if (!in_array($attrCodes, $this->keysAdditional)) {
                                    $this->keysAdditional[] = $attrCodes;
                                }
                                $addtionalFields[$attrCodes] = $this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes];
                            }
                        }
                    }
                }
                if (!empty($additionalAttributes)) {
                    $additionalAttributes = array_map('htmlspecialchars_decode', $additionalAttributes);
                    $data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalAttributes);
                } else {
                    unset($data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES]);
                }

                if (!empty($data[$itemId][$storeId]) || $this->hasMultiselectData($item, $storeId)) {
                    $attrSetId = $item->getAttributeSetId();
                    $data[$itemId][$storeId][self::COL_STORE] = $storeCode;
                    $data[$itemId][$storeId][self::COL_ATTR_SET] = $this->_attrSetIdToName[$attrSetId];
                    $data[$itemId][$storeId][self::COL_TYPE] = $item->getTypeId();
                }
                if (!empty($addtionalFields)) {
                    foreach ($addtionalFields as $key => $value) {
                        $data[$itemId][$storeId][$key] = $value;
                    }
                }
                $data[$itemId][$storeId][self::COL_SKU] = htmlspecialchars_decode($item->getSku());
                $data[$itemId][$storeId]['store_id'] = $storeId;
                $data[$itemId][$storeId]['product_id'] = $itemId;
                $data[$itemId][$storeId]['product_link_id'] = $productLinkId;
            }
        }

        return $data;
    }

    private function wrapValue(
        $value
    ) {
        if (!empty($this->_parameters[\Magento\ImportExport\Model\Export::FIELDS_ENCLOSURE])) {
            $wrap = function ($value) {
                return sprintf('"%s"', str_replace('"', '""', $value));
            };

            $value = is_array($value) ? array_map($wrap, $value) : $wrap($value);
        }

        return $value;
    }

    /**
     * @param $stockItemRows
     */
    protected function setAddHeaderColumns($stockItemRows)
    {
        $addData = [];

        if (!empty($stockItemRows)) {
            if (reset($stockItemRows)) {
                $addData = array_keys(end($stockItemRows));
                foreach ($addData as $key => $value) {
                    if (is_numeric($value)) {
                        unset($addData[$key]);
                    }
                }
            }
        }
        if (!$this->_headerColumns) {
            $this->_headerColumns = array_merge(
                [
                    self::COL_SKU,
                    self::COL_STORE,
                    self::COL_ATTR_SET,
                    self::COL_TYPE,
                    self::COL_CATEGORY,
                    self::COL_PRODUCT_WEBSITES,
                ],
                $this->_getExportMainAttrCodes(),
                [self::COL_ADDITIONAL_ATTRIBUTES],
                $addData,
                [
                    'related_skus',
                    'related_position',
                    'crosssell_skus',
                    'crosssell_position',
                    'upsell_skus',
                    'upsell_position',
                    'additional_images',
                    'additional_image_labels',
                    'hide_from_product_page',
                    'custom_options'
                ]
            );
            if ($this->moduleManager->isEnabled('MageArray_MaMarketPlace')
                || $this->moduleManager->isEnabled('Webkul_Marketplace')
            ) {
                $this->_headerColumns = \array_merge(
                    $this->_headerColumns,
                    [
                        'vendor_id'
                    ]
                );
            }
        }
    }

    protected function addHeaderColumns()
    {
        if ($this->checkDivideofAttributes()) {
            $this->_headerColumns = array_merge($this->_headerColumns, $this->keysAdditional);
        }
    }

    protected function fireloadCollection()
    {
        $data = [];

        $collection = $this->_getEntityCollection();

        foreach (array_keys($this->getStores()) as $storeId) {
            $collection->clear();
            $collection->addStoreFilter($storeId);

            foreach ($collection as $itemId => $item) {
                $data[$itemId][$storeId] = $item;
            }
        }
        $collection->clear();

        return $data;
    }

    protected function checkDivideofAttributes()
    {
        return isset($this->_parameters['divided_additional']) && $this->_parameters['divided_additional'];
    }

    /**
     * @param array $dataRow
     * @param array $multiRawData
     *
     * @return array|null
     */
    protected function appendMultirowData(&$dataRow, &$multiRawData)
    {
        $pId = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId = $dataRow['store_id'];
        $sku = $dataRow[self::COL_SKU];

        //unset($dataRow['product_id']);
        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        unset($dataRow[self::COL_STORE]);

        $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $pId);
        if (!empty($multiRawData['rowWebsites'][$pId])) {
            $websiteCodes = [];
            foreach ($multiRawData['rowWebsites'][$pId] as $productWebsite) {
                $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
            }
            $dataRow[self::COL_PRODUCT_WEBSITES] =
                implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $websiteCodes);
            $multiRawData['rowWebsites'][$pId] = [];
        }

        $multiRawData['mediaGalery'] = $this->getMediaGallery([$productLinkId]);
        if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
            $additionalImages = [];
            $additionalLabels = [];
            $additionalImageIsDisabled = [];
            foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                $additionalImages[] = $mediaItem['_media_image'];
                $additionalLabels[] = $mediaItem['_media_label'];
                if ($mediaItem['_media_is_disabled'] == true) {
                    $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                }
            }

            $dataRow['additional_images'] = implode(
                Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                $additionalImages
            );
            $dataRow['additional_image_labels'] = implode(
                Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                $additionalLabels
            );
            $dataRow['hide_from_product_page'] = implode(
                Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                $additionalImageIsDisabled
            );
            $multiRawData['mediaGalery'][$productLinkId] = [];
        }
        foreach ($this->_linkTypeProvider->getLinkTypes() as $typeName => $linkId) {
            if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                $colPrefix = $typeName . '_';
                $associations = [];
                foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                    if ($linkData['default_qty'] !== null) {
                        $skuItem = $linkData['sku']
                            . ImportProduct::PAIR_NAME_VALUE_SEPARATOR
                            . $linkData['default_qty'];
                    } else {
                        $skuItem = $linkData['sku'];
                    }
                    $associations[$skuItem] = $linkData['position'];
                }
                $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                asort($associations);
                $dataRow[$colPrefix . 'skus'] = implode(
                    Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                    array_keys($associations)
                );
                $dataRow[$colPrefix . 'position'] = implode(
                    Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                    array_values($associations)
                );
            }
        }
        $dataRow = $this->rowCustomizer->addData($dataRow, $pId);

        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productLinkId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$attrKey])) {
                    $dataRow[$attrKey] =
                        implode(
                            Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                            $this->collectedMultiselectsData[$storeId][$productLinkId][$attrKey]
                        );
                }
            }
        }

        if (!empty($multiRawData['customOptionsData'][$productLinkId][$storeId])) {
            $customOptionsRows =
                $multiRawData['customOptionsData'][$productLinkId][$storeId];
            $multiRawData['customOptionsData'][$productLinkId][$storeId] = [];
            $customOptions =
                implode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $customOptionsRows);

            $dataRow = array_merge(
                $dataRow,
                ['custom_options' => $customOptions]
            );
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
        }
        $dataRow[self::COL_SKU] = $sku;

        return $dataRow;
    }

    /**
     * Filter by stores
     *
     * @return array
     */
    protected function getStores()
    {
        $stores = [];
        if (
            isset($this->_parameters['behavior_data']['store_ids'])
            && is_array($this->_parameters['behavior_data']['store_ids'])
        ) {
            $storeIds = $this->_parameters['behavior_data']['store_ids'];
            foreach ($this->_storeIdToCode as $id => $code) {
                if (in_array($id, $storeIds)) {
                    $stores[$id] = $code;
                }
            }
        } else {
            $stores = $this->_storeIdToCode;
        }

        return $stores;
    }
}
