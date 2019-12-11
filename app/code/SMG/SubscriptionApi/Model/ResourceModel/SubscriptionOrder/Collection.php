<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionOrder::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder::class
        );
    }
}