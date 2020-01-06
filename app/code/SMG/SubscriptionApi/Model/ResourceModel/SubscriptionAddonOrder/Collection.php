<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionAddonOrder::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder::class
        );
    }
}