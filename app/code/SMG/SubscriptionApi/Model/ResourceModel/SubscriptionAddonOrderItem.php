<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionAddonOrderItem
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionAddonOrderItem extends AbstractDb
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_addon_order_item',
            'entity_id'
        );
    }
}