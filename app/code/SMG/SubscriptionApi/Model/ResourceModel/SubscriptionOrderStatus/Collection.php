<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderStatus;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderStatus
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionOrderStatus::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderStatus::class
        );
    }
}