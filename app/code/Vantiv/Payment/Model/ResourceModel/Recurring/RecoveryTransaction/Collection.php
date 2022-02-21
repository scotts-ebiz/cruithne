<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\ResourceModel\Recurring\RecoveryTransaction;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Flag that indicates if plans table has been joined
     *
     * @var bool
     */
    private $subscriptionsJoined = false;

    /**
     * Define model and resource model, set default order
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vantiv\Payment\Model\Recurring\RecoveryTransaction',
            'Vantiv\Payment\Model\ResourceModel\Recurring\RecoveryTransaction'
        );
    }

    /**
     * Join subscriptions table
     *
     * @param string|array $cols
     * @return $this
     */
    protected function joinSubscriptions($cols = \Magento\Framework\DB\Select::SQL_WILDCARD)
    {
        if ($this->subscriptionsJoined) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            ['subscriptions' => 'vantiv_subscriptions'],
            'subscriptions.subscription_id = main_table.subscription_id',
            $cols
        );

        $this->subscriptionsJoined = true;

        return $this;
    }
}
