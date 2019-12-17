<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionAddonOrderItem::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem::class
        );
    }
}