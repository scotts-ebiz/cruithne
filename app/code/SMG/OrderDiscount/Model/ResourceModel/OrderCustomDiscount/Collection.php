<?php

namespace SMG\OrderDiscount\Model\ResourceModel\OrderCustomDiscount;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\SMG\OrderDiscount\Model\OrderCustomDiscount::class,
            \SMG\OrderDiscount\Model\ResourceModel\OrderCustomDiscount::class);
    }
}
