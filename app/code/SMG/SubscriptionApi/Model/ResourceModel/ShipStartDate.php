<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionType
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class ShipStartDate extends AbstractDb
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_order',
            'ship_start_date'
        );
    }
}