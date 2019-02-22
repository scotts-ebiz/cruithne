<?php

namespace SMG\CreditReason\Model;

use Magento\Framework\Model\AbstractModel;

class CreditReasonCode extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\SMG\CreditReason\Model\ResourceModel\CreditReasonCode::class);
    }
}