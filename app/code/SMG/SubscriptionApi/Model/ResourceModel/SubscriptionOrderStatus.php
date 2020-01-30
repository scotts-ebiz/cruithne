<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionOrderStatus
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionOrderStatus extends AbstractDb
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_order_status',
            'status'
        );
    }
}