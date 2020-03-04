<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SubscriptionType
 * @package SMG\SubscriptionApi\Model
 */
class ShipStartDate extends AbstractModel
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\ShipStartDate::class
        );
    }
}