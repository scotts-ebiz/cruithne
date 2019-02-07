<?php

namespace SMG\OfflineShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ShippingConditionCode extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('shipping_condition_code', 'entity_id');
    }
}