<?php

namespace SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderRenewal;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package SMG\SubscriptionApi\Model\ResourceModel\SubscriptionRenewalError
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\SubscriptionRenewalError::class,
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionRenewalError::class
        );
    }
}
