<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'addon_id';

    /**
     * Reset items data changed flag
     *
     * @var boolean
     */
    protected $_resetItemsDataChanged = true;

    /**
     * Define model and resource model, set default order
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vantiv\Payment\Model\Recurring\Subscription\Addon',
            'Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon'
        );
    }

    /**
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @return $this
     */
    public function setSubscriptionFilter(\Vantiv\Payment\Model\Recurring\Subscription $subscription)
    {
        $this->addFieldToFilter('subscription_id', $subscription->getId());

        return $this;
    }
}
