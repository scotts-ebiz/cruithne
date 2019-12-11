<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SubscriptionOrder
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionOrder extends AbstractModel
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder::class
        );
    }
}