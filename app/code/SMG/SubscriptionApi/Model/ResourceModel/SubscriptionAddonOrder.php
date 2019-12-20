<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionAddonOrder
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionAddonOrder extends AbstractDb
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_addon_order',
            'entity_id'
        );
    }
}