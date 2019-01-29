<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class Address extends \Magento\CustomerImportExport\Model\Export\Address
{
    use \Firebear\ImportExport\Traits\Export\Entity;

    use \Firebear\ImportExport\Traits\General;


    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        return array_unique(
            array_merge(
                $this->_permanentAttributes,
                $this->_getExportAttributeCodes(),
                array_keys(self::$_defaultAddressAttributeMapping)
            )
        );
    }

    /**
     * @param $item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportItem($item)
    {
        $row = $this->_addAttributeValuesToRow($item);

        $customer = $this->_customers[$item->getParentId()];

        foreach (self::$_defaultAddressAttributeMapping as $columnName => $attributeCode) {
            if (!empty($customer[$attributeCode]) && $customer[$attributeCode] == $item->getId()) {
                $row[$columnName] = 1;
            }
        }
        if ($this->_parameters['enable_last_entity_id'] > 0) {
            $this->lastEntityId = $item['entity_id'];
        }

        $row[self::COLUMN_ADDRESS_ID] = $item['entity_id'];
        $row[self::COLUMN_EMAIL] = $customer['email'];
        $row[self::COLUMN_WEBSITE] = $this->_websiteIdToCode[$customer['website_id']];

        $this->getWriter()->writeRow($this->changeRow($row));
    }

    public function export()
    {
//        $this->lastEntityId = '';
        // skip and filter by customer address attributes
        $entityCollection = $this->_getEntityCollection();
        if (isset($this->_parameters['last_entity_id'])
            && $this->_parameters['last_entity_id'] > 0
            && $this->_parameters['enable_last_entity_id'] > 0
        ) {
            $entityCollection->addFieldToFilter(
                'entity_id', ['gt' => $this->_parameters['last_entity_id']]
            );
        }
        $this->_prepareEntityCollection($entityCollection);
        $entityCollection->setCustomerFilter(array_keys($this->_customers));

        // prepare headers
        $this->getWriter()->setHeaderCols($this->_getHeaderColumns());

        $this->_exportCollectionByPages($entityCollection);

        return [$this->getWriter()->getContents(), $entityCollection->getSize(), $this->lastEntityId];
    }

    /**
     * @return mixed
     */
    protected function _getHeaderColumns()
    {
        $headers = array_merge(
            $this->_permanentAttributes,
            $this->_getExportAttributeCodes(),
            array_keys(self::$_defaultAddressAttributeMapping)
        );

        return $this->changeHeaders($headers);
    }
	
    /**
     * Apply filter to collection and add not skipped attributes to select
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        $this->filterEntityCollection($collection);
        $this->_addAttributesToCollection($collection);
        return $collection;
    }	
}
