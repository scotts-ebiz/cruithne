<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SubscriptionOrderItem
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionOrderItem extends AbstractModel
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem::class
        );
    }
}