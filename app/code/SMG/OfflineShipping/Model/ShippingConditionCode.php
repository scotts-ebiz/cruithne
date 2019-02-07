<?php

namespace SMG\OfflineShipping\Model;

use Magento\Framework\Model\AbstractModel;

class ShippingConditionCode extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode::class);
    }
}