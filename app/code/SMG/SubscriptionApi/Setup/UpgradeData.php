<?php

namespace Mageplaza\HelloWorld\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \SMG\SubscriptionApi\Model\SubscriptionStatusFactory
     */
    protected $_subscriptionStatusFactory;

    /**
     * @var \SMG\SubscriptionApi\Model\SubscriptionTypeFactory
     */
    protected $_subscriptionTypeFactory;

    /**
     * @var \SMG\SubscriptionApi\Model\SubscriptionOrderStatusFactory
     */
    protected $_subscriptionOrderStatusFactory;

    /**
     * UpgradeData constructor.
     * @param \SMG\SubscriptionApi\Model\SubscriptionStatusFactory $subscriptionStatusFactory
     * @param \SMG\SubscriptionApi\Model\SubscriptionTypeFactory $subscriptionTypeFactory
     * @param \SMG\SubscriptionApi\Model\SubscriptionOrderStatusFactory $subscriptionOrderStatusFactory
     */
    public function __construct(
        \SMG\SubscriptionApi\Model\SubscriptionStatusFactory $subscriptionStatusFactory,
        \SMG\SubscriptionApi\Model\SubscriptionTypeFactory $subscriptionTypeFactory,
        \SMG\SubscriptionApi\Model\SubscriptionOrderStatusFactory $subscriptionOrderStatusFactory
    )
    {
        $this->_subscriptionStatusFactory = $subscriptionStatusFactory;
        $this->_subscriptionTypeFactory = $subscriptionTypeFactory;
        $this->_subscriptionOrderStatusFactory = $subscriptionOrderStatusFactory;
    }

    /**
     * Upgrade function.
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        // Version 1.1.1
        if ( version_compare( $context->getVersion(), '1.1.1', 'eq' )) {

            // Upgrade Subscription Status
            $subscriptionStatus = $this->_subscriptionStatusFactory->create();
            $subscriptionStatus->addData(['status' => 'pending', 'label' => 'Pending'])->save();
            $subscriptionStatus = $this->_subscriptionStatusFactory->create();
            $subscriptionStatus->addData(['status' => 'active', 'label' => 'Active'])->save();
            $subscriptionStatus = $this->_subscriptionStatusFactory->create();
            $subscriptionStatus->addData(['status' => 'complete', 'label' => 'Complete'])->save();
            $subscriptionStatus = $this->_subscriptionStatusFactory->create();
            $subscriptionStatus->addData(['status' => 'abandoned', 'label' => 'Abandoned'])->save();

            // Upgrade Subscription Type
            $subscriptionTypeFactory = $this->_subscriptionTypeFactory->create();
            $subscriptionTypeFactory->addData(['type' => 'annual', 'label' => 'Annual Subscription'])->save();
            $subscriptionTypeFactory = $this->_subscriptionTypeFactory->create();
            $subscriptionTypeFactory->addData(['type' => 'seasonal', 'label' => 'Seasonal Subscription'])->save();

            // Upgrade Subscription Order Status
            $subscriptionOrderStatusFactory = $this->_subscriptionOrderStatusFactory->create();
            $subscriptionOrderStatusFactory->addData(['status' => 'pending', 'label' => 'Pending'])->save();
            $subscriptionOrderStatusFactory = $this->_subscriptionOrderStatusFactory->create();
            $subscriptionOrderStatusFactory->addData(['status' => 'complete', 'label' => 'Complete'])->save();
            $subscriptionOrderStatusFactory = $this->_subscriptionOrderStatusFactory->create();
            $subscriptionOrderStatusFactory->addData(['status' => 'canceled', 'label' => 'Canceled'])->save();

        } // End Version 1.1.1
    }
}