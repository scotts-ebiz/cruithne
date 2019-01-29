<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use \Magento\Store\Model\Store;

class AdvancedPricing extends \Magento\AdvancedPricingImportExport\Model\Export\AdvancedPricing
{
    use \Firebear\ImportExport\Traits\Export\Products;

    use \Firebear\ImportExport\Traits\General;

    /**
     * @return array
     */
    protected function getExportData()
    {
        $data = parent::getExportData();
        $newData = $this->changeData($data);
        $this->_headerColumns = $this->changeHeaders($this->_headerColumns);

        return $newData;
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

    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $countes = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('has_options', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            foreach ($exportData as $dataRow) {
                $writer->writeRow($dataRow);
                $countes++;
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }
        return [$writer->getContents(), $countes];
    }
}
