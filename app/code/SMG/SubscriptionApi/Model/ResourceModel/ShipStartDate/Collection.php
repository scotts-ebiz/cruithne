<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\ShipStartDate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionType
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ShipStartDate::class,
            \SMG\SubscriptionApi\Model\ResourceModel\ShipStartDate::class
        );
    }
}