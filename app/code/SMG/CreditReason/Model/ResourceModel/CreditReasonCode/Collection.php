<?php

namespace SMG\CreditReason\Model\ResourceModel\CreditReasonCode;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\SMG\CreditReason\Model\CreditReasonCode::class,
            \SMG\CreditReason\Model\ResourceModel\CreditReasonCode::class);
    }
}