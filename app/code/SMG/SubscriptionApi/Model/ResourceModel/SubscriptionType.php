<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SubscriptionType
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class SubscriptionType extends AbstractDb
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            'subscription_type',
            'type'
        );
    }
}