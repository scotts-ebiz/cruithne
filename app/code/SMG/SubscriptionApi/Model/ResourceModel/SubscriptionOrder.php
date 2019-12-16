<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionOrder
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionOrder extends AbstractDb
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_order',
            'entity_id'
        );
    }
}