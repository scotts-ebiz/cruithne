<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\Subscription;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\Subscription
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\Subscription::class,
            \SMG\SubscriptionApi\Model\ResourceModel\Subscription::class
        );
    }
}