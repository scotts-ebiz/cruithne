<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionOrderItem
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionOrderItem extends AbstractDb
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_order_item',
            'entity_id'
        );
    }
}