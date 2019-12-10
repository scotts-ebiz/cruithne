<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/9/19
 * Time: 3:55 PM
 */

namespace SMG\Sales\Model;

use Magento\Sales\Model\Order as MagentoOrder;


class Order extends MagentoOrder
{
    /**
     * This will determine if the order is a subscription or
     * a regular order
     *
     * @return bool
     */
    public function isSubscription()
    {
        // variables
        $returnValue = false;

        // if there is a type and there is a master id or a subscription id
        // then it is a subscription
        if (!empty($this->getData('subscription_type')) && (!empty($this->getData('master_subscription_id')) || !empty($this->getData('subscription_id'))))
        {
            $returnValue = true;
        }

        // return whether this was a subscription or not
        return $returnValue;
    }

    /**
     * This function will get the subscription order id based
     * on the subscription type
     *
     * @return mixed
     */
    public function getSubscriptionOrderId()
    {
        // get the subscription id for annuals
        // this will be the default return
        $subscriptionOrderId = $this->getData('master_subscription_id');
        if ($this->getData('subscription_type') == 'seasonal')
        {
            $subscriptionOrderId = $this->getData('subscription_id');
        }

        // return the appropriate subscription id
        return $subscriptionOrderId;
    }
}