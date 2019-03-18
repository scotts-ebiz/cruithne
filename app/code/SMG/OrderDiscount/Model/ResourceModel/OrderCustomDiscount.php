<?php

namespace SMG\OrderDiscount\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrderCustomDiscount extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('smg_discount_codes', 'entity_id');
    }
}
