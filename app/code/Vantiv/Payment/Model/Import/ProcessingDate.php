<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Import;

class ProcessingDate extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vantiv\Payment\Model\ResourceModel\Import\ProcessingDate');
    }

    /**
     * Load object data by import code and merchant id
     *
     * @param string $importCode
     * @param string $merchantId
     * @return $this
     */
    public function loadByImportCodeAndMerchantId($importCode, $merchantId)
    {
        $this->_getResource()->loadByImportCodeAndMerchantId($this, $importCode, $merchantId);
        $this->_afterLoad();
        $this->setOrigData();
        $this->_hasDataChanges = false;
        return $this;
    }
}
