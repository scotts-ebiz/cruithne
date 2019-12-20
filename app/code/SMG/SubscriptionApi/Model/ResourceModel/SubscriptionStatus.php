<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionStatus
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionStatus extends AbstractDb
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_status',
            'status'
        );
    }
}