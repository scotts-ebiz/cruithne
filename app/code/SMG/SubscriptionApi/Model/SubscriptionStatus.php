<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SubscriptionStatus
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionStatus extends AbstractModel
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionStatus::class
        );
    }
}