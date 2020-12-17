<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SubscriptionOrderStatus
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionRenewalError extends AbstractModel
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionRenewalError::class
        );
    }
}
