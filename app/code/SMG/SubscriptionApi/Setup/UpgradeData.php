<?php

namespace SMG\SubscriptionApi\Setup;

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
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {

        // Version 1.1.2
        if ( version_compare( $context->getVersion(), '1.1.2', '<=' ) ) {
            $this->addDataVersion110($setup);

        } // End Version 1.1.1
    }

    /**
     * Add Data for Version 1.1.2
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion110(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_status');
        $data = [
            ['status' => 'pending', 'label' => 'Pending'],
            ['status' => 'active', 'label' => 'Active'],
            ['status' => 'complete', 'label' => 'Complete'],
            ['status' => 'abandoned', 'label' => 'Abandoned']
        ];
        $setup->getConnection()->insertMultiple($tableName, $data);

        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_type');
        $data = [
            ['type' => 'annual', 'label' => 'Annual Subscription'],
            ['type' => 'seasonal', 'label' => 'Seasonal Subscription']
        ];
        $setup->getConnection()->insertMultiple($tableName, $data);

        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_order_status');
        $data = [
            ['status' => 'pending', 'label' => 'Pending'],
            ['status' => 'complete', 'label' => 'Complete'],
            ['status' => 'canceled', 'label' => 'Canceled']
        ];
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}