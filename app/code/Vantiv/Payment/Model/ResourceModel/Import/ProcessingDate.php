<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\ResourceModel\Import;

class ProcessingDate extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vantiv_import_processing_date', 'entity_id');
    }

    /**
     * Load an object by import code and merchant id
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param string $importCode
     * @param string $merchantId
     * @return $this
     */
    public function loadByImportCodeAndMerchantId(
        \Magento\Framework\Model\AbstractModel $object,
        $importCode,
        $merchantId
    ) {
        $connection = $this->getConnection();
        if ($connection && $importCode !== null && $merchantId !== null) {
            $select = $this->getConnection()->select()->from($this->getMainTable())
                ->where(
                    $this->getConnection()
                        ->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'import_code')) . '=?',
                    $importCode
                )->where(
                    $this->getConnection()
                        ->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'merchant_id')) . '=?',
                    $merchantId
                );
            $data = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }
}
