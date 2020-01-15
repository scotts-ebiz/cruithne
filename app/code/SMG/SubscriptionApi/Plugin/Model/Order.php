<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 1/15/20
 * Time: 8:06 AM
 */

namespace SMG\SubscriptionApi\Plugin\Model;

use Psr\Log\LoggerInterface;

class Order
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Order constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function beforePlace(\Magento\Sales\Model\Order $subject)
    {
        try
        {
            $this->_logger->info("I am in the beforePlace");
            $this->_logger->info("Is this a subscription: " . $subject->isSubscription());
            $this->_logger->info("Subscription Type: " . $subject->getData('subscription_type'));
            $this->_logger->info("Master Subscriptino Id: " . $subject->getData('master_subscription_id'));
            $this->_logger->info("Subscription Id: " . $subject->getData('subscription_id'));
            $this->_logger->info("Send New Email Flag Before: " . $subject->getCanSendNewEmailFlag());

            // Set the send flag to false
            // so new emails are not sent for every order created
            // on subscriptions
            if (!empty($subject->getData('subscription_type')) && (!empty($subject->getData('master_subscription_id')) || !empty($subject->getData('subscription_id'))))
            {
                $subject->setCanSendNewEmailFlag(0);
            }

            $this->_logger->info("Send New Email Flag After: " . $subject->getCanSendNewEmailFlag());
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e->getMessage());
        }
    }
}