<?php

namespace SMG\OrderDiscount\Model;

use Magento\Framework\Model\AbstractModel;

class OrderCustomDiscount extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\SMG\OrderDiscount\Model\ResourceModel\OrderCustomDiscount::class);
    }
}
