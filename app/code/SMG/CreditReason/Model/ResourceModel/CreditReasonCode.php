<?php

namespace SMG\CreditReason\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CreditReasonCode extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('credit_reason_code', 'entity_id');
    }
}