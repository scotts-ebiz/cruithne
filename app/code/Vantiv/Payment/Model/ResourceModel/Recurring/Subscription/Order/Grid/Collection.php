<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Order\Grid;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Grid\Collection
{
    protected $_map = ['fields' => ['subscription_id' => 'vantiv_subscription_id']];
}
