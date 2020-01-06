<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionOrderItem::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem::class
        );
    }
}