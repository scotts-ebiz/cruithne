<?php

namespace SMG\SubscriptionApi\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

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
    ) {
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
    ) {

        // Version 1.1.2
        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            $this->addDataVersion112($setup);
        }

        // Version 1.1.3
        if (version_compare($context->getVersion(), '1.1.3', '<')) {
            $this->addDataVersion113($setup);
        }

        // Version 1.1.4
        if (version_compare($context->getVersion(), '1.1.4', '<')) {
            $this->addDataVersion114($setup);
        }

        // Version 1.1.5
        if (version_compare($context->getVersion(), '1.1.5', '<')) {
            $this->addDataVersion115($setup);
        }

        // Version 1.1.6
        if (version_compare($context->getVersion(), '1.1.6', '<')) {
            $this->addDataVersion116($setup);
        }

        // Version 1.1.7
        if (version_compare($context->getVersion(), '1.1.7', '<')) {
            $this->addDataVersion117($setup);
        }

        // Version 1.1.8
        if (version_compare($context->getVersion(), '1.1.8', '<')) {
            $this->addDataVersion118($setup);
        }

        // Version 1.1.9
        if (version_compare($context->getVersion(), '1.1.9', '<')) {
            $this->addDataVersion119($setup);
        }
    }

    /**
     * Add Data for Version 1.1.2
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion112(ModuleDataSetupInterface $setup)
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

    /**
     * Add Data for Version 1.1.3
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion113(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_status');
        $data = [
            ['status' => 'pending_order', 'label' => 'Pending Order']
        ];
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 1.1.4
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion114(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_status');
        $data = [
            ['status' => 'canceled', 'label' => 'Canceled']
        ];
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 1.1.5
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion115(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_order_status');

        $data = [
            ['status' => 'failed', 'label' => 'Failed']
        ];

        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 1.1.6
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion116(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_status');

        $data = [
            ['status' => 'renewed', 'label' => 'Renewed']
        ];

        $setup->getConnection()->insertMultiple($tableName, $data);
        // Do nothing. Done purposefully to resolve merge.
    }

    /**
     * Add Data for Version 1.1.7
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion117(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_order_status');

        $data = [
            ['status' => 'initialized', 'label' => 'Initialized'],
            ['status' => 'invoiced', 'label' => 'Invoiced'],
            ['status' => 'sent_for_fulfillment', 'label' => 'Sent for Fulfillment'],
            ['status' => 'partially_shipped', 'label' => 'Partially Shipped'],
            ['status' => 'shipped', 'label' => 'Shipped'],
            ['status' => 'delivered', 'label' => 'Delivered'],
            ['status' => 'audit_failed', 'label' => 'Audit Failed'],
            ['status' => 'skipped', 'label' => 'Skipped'],
        ];

        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 1.1.8
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion118(ModuleDataSetupInterface $setup)
    {
        // Upgrade Subscription Status
        $tableName = $setup->getTable('subscription_order_status');

        $data = [
            ['status' => 'processing', 'label' => 'Processing'],
        ];

        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 1.1.9
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion119(ModuleDataSetupInterface $setup)
    {
        // Do nothing. Done purposefully to resolve merge.
    }
}
