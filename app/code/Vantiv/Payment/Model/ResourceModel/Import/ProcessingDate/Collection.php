<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\ResourceModel\Import\ProcessingDate;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model and resource model, set default order
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vantiv\Payment\Model\Import\ProcessingDate',
            'Vantiv\Payment\Model\ResourceModel\Import\ProcessingDate'
        );
    }
}
