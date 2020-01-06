<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionStatus;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionStatus
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionStatus::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionStatus::class
        );
    }
}