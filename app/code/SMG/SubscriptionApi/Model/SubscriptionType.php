<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SubscriptionType
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionType extends AbstractModel
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionType::class
        );
    }
}