<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->createRecurringPlansTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->fixTxnColNameInRecurringPlansTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.4', '<')) {
            $this->fixPlanIdColDefInRecurringPlansTable($setup)
                ->createSubscriptionsTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            $this->addCustomerNameToSubscriptionsTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $this->fixSubscriptionIdColDefInSubscriptionsTable($setup);
            $this->addSubscriptionIdToOrder($setup);
            $this->createSubscriptionAddonTable($setup);
            $this->createSubscriptionDiscountTable($setup);
            $this->addCustomerEmailToSubscriptionsTable($setup);
            $this->addStoreNameToSubscriptionsTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.7', '<')) {
            $this->createCertificationTestResultsTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.9', '<')) {
            $this->addOrderDataColumnsToSubscriptionsTable($setup);
            $this->addOrderItemDataColumnsToSubscriptionsTable($setup);
            $this->addPaymentDataColumnsToSubscriptionTable($setup);
            $this->createSubscriptionAddressTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.10', '<')) {
            $this->convertBigintColumns($setup);
        }

        if (version_compare($context->getVersion(), '2.0.11', '<')) {
            $this->createRecoveryTransactionTable($setup);
            $this->addIndexOnOrderIncrIdToSubscrTable($setup);
            $this->createImportProcessingDateTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.12', '<')) {
            $this->createSubscriptionAmountChangelogTable($setup);
            $this->addIsSystemColumnToAddonTable($setup);
            $this->addIsSystemColumnToDiscountTable($setup);
        }
    }

    /**
     * Create Recurring Plans table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createRecurringPlansTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_recurring_plans'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_recurring_plans')
        )->addColumn(
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Plan ID'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Product ID'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => '0'],
            'Website ID'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Description'
        )->addColumn(
            'number_of_payments',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Number of Payments'
        )->addColumn(
            'interval',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Interval'
        )->addColumn(
            'interval_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Interval Amount'
        )->addColumn(
            'trial_interval',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Trial Interval'
        )->addColumn(
            'number_of_trial_intervals',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Number of Trial Intervals'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '0'],
            'Sort Order'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'default' => '0'],
            'Active'
        )->addColumn(
            'little_txn_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Vantiv Transaction ID'
        )->addIndex(
            $installer->getIdxName('vantiv_recurring_plans', ['code']),
            ['code']
        )->addIndex(
            $installer->getIdxName('vantiv_recurring_plans', ['active', 'sort_order']),
            ['active', 'sort_order']
        )->addForeignKey(
            $installer->getFkName('vantiv_recurring_plans', 'product_id', 'catalog_product_entity', 'entity_id'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName('vantiv_recurring_plans', 'website_id', 'store_website', 'website_id'),
            'website_id',
            $installer->getTable('store_website'),
            'website_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Vantiv Recurring Plans'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create Recurring Plans table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function fixTxnColNameInRecurringPlansTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $installer->getConnection()->changeColumn(
            $installer->getTable('vantiv_recurring_plans'),
            'little_txn_id',
            'litle_txn_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                'nullable' => false,
                'unsigned' => true,
                'comment' => 'Vantiv Transaction ID'
            ]
        );

        return $this;
    }

    /**
     * Fix plan id column definition in recurring plans table (make it unsigned)
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function fixPlanIdColDefInRecurringPlansTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $installer->getConnection()->changeColumn(
            $installer->getTable('vantiv_recurring_plans'),
            'plan_id',
            'plan_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'identity' => true,
                'nullable' => false,
                'unsigned' => true,
                'primary' => true,
                'comment' => 'Plan ID'
            ]
        );

        return $this;
    }

    /**
     * Create subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_subscriptions'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_subscriptions')
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Subscription ID'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true],
            'Store Id'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->addColumn(
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Plan ID'
        )->addColumn(
            'interval_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Interval Amount'
        )->addColumn(
            'start_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'Start Date'
        )->addColumn(
            'vantiv_subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Vantiv Subscription ID'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Customer ID'
        )->addColumn(
            'original_order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Original Order ID'
        )->addColumn(
            'original_order_increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Original Order Increment ID'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Product ID'
        )->addColumn(
            'product_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Product Name'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Status'
        )->addIndex(
            $installer->getIdxName('vantiv_subscriptions', ['status']),
            ['status']
        )->addIndex(
            $installer->getIdxName('vantiv_subscriptions', ['vantiv_subscription_id']),
            ['vantiv_subscription_id']
        )->addIndex(
            $installer->getIdxName('vantiv_subscriptions', ['store_id']),
            ['store_id']
        )->addIndex(
            $installer->getIdxName('vantiv_subscriptions', ['customer_id', 'store_id']),
            ['customer_id', 'store_id']
        )->addForeignKey(
            $installer->getFkName('vantiv_subscriptions', 'store_id', 'store', 'store_id'),
            'store_id',
            $installer->getTable('store'),
            'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName('vantiv_subscriptions', 'plan_id', 'vantiv_recurring_plans', 'plan_id'),
            'plan_id',
            $installer->getTable('vantiv_recurring_plans'),
            'plan_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName('vantiv_subscriptions', 'customer_id', 'customer_entity', 'entity_id'),
            'customer_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName('vantiv_subscriptions', 'original_order_id', 'sales_order', 'entity_id'),
            'original_order_id',
            $installer->getTable('sales_order'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName('vantiv_subscriptions', 'product_id', 'catalog_product_entity', 'entity_id'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->setComment(
            'Vantiv Subscriptions'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Fix subscription id column definition in subscriptionss table (make it unsigned)
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function fixSubscriptionIdColDefInSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $installer->getConnection()->changeColumn(
            $installer->getTable('vantiv_subscriptions'),
            'subscription_id',
            'subscription_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'identity' => true,
                'nullable' => false,
                'unsigned' => true,
                'primary' => true,
                'comment' => 'Subscription ID'
            ]
        );

        return $this;
    }

    /**
     * Add billing and shipping names to subscription table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addCustomerNameToSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'billing_name',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Billing Name',
                'after' => 'product_name'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'shipping_name',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Shipping Name',
                'after' => 'billing_name'
            ]
        );

        $connection->addIndex(
            $installer->getTable('vantiv_subscriptions'),
            $installer->getIdxName(
                'vantiv_subscriptions',
                [
                    'original_order_increment_id',
                    'product_name',
                    'billing_name',
                    'shipping_name'
                ],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            [
                'original_order_increment_id',
                'product_name',
                'billing_name',
                'shipping_name'
            ],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
        );

        return $this;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addSubscriptionIdToOrder(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable('sales_order'),
            'vantiv_subscription_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'comment' => 'Vantiv Subscription ID'
            ]
        );

        $connection->addIndex(
            $installer->getTable('sales_order'),
            $installer->getIdxName('sales_order', 'vantiv_subscription_id'),
            'vantiv_subscription_id'
        );

        $connection->addColumn(
            $installer->getTable('sales_order_grid'),
            'vantiv_subscription_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'comment' => 'Vantiv Subscription ID'
            ]
        );

        $connection->addIndex(
            $installer->getTable('sales_order_grid'),
            $installer->getIdxName('sales_order_grid', 'vantiv_subscription_id'),
            'vantiv_subscription_id'
        );

        return $this;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionAddonTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_subscription_addon'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_subscription_addon')
        )->addColumn(
            'addon_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
            'Addon ID'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Subscription ID'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Name'
        )->addColumn(
            'amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Amount'
        )->addColumn(
            'start_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'Start Date'
        )->addColumn(
            'end_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'End Date'
        )->addForeignKey(
            $installer->getFkName(
                'vantiv_subscription_addon',
                'subscription_id',
                'vantiv_subscriptions',
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable('vantiv_subscriptions'),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionDiscountTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_subscription_discount'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_subscription_discount')
        )->addColumn(
            'discount_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
            'Discount ID'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Subscription ID'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Name'
        )->addColumn(
            'amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true],
            'Amount'
        )->addColumn(
            'start_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'Start Date'
        )->addColumn(
            'end_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'End Date'
        )->addForeignKey(
            $installer->getFkName(
                'vantiv_subscription_discount',
                'subscription_id',
                'vantiv_subscriptions',
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable('vantiv_subscriptions'),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Add customer email column to subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addCustomerEmailToSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'customer_email',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 128,
                'comment' => 'Customer Email',
                'after' => 'shipping_name'
            ]
        );
        return $this;
    }

    /**
     * Add store name column to subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addStoreNameToSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'store_name',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Store Name',
                'after' => 'store_id'
            ]
        );
        return $this;
    }

    /**
     * Create Certification Test Results table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createCertificationTestResultsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_certification_results'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_certification_results')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Certification Test Result ID'
        )->addColumn(
            'test_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => false],
            'Certification Test ID'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Certification Test Title'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Store ID'
        )->addColumn(
            'merchant_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Merchant ID'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Order ID'
        )->addColumn(
            'little_txn_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Vantiv Transaction ID'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Creation Time'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Update Time'
        )->addColumn(
            'success_flag',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'dafault' => false],
            'Success Flag'
        )->addColumn(
            'request',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Certification Test Request'
        )->addColumn(
            'response',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Certification Test Response'
        )->addIndex(
            $installer->getIdxName('vantiv_certification_results', ['test_id']),
            ['test_id']
        )->addIndex(
            $installer->getIdxName('vantiv_certification_results', ['store_id']),
            ['store_id']
        )->addIndex(
            $installer->getIdxName('vantiv_certification_results', ['test_id', 'store_id']),
            ['test_id', 'store_id']
        )->addForeignKey(
            $installer->getFkName('vantiv_certification_results', 'store_id', 'store', 'store_id'),
            'store_id',
            $installer->getTable('store'),
            'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Vantiv Certification Test Results'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Add order data columns to subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addOrderDataColumnsToSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'shipping_method',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'comment' => 'Shipping Description'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'shipping_description',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Shipping Description'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'is_virtual',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'comment' => 'Is Virtual'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'discount_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Discount Amount'
            ]
        );
        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'discount_description',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Discount Description'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'shipping_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Shipping Amount'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'shipping_tax_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Shipping Tax Amount'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'subtotal',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Order Subtotal'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'tax_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Tax Amount'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'subtotal_incl_tax',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Subtotal Incl Tax'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'forced_shipment_with_invoice',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'comment' => 'Forced Do Shipment With Invoice'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'weight',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Weight'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'customer_note',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '64k',
                'comment' => 'Customer Note'
            ]
        );

        return $this;
    }

    /**
     * Add order item data columns to subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addOrderItemDataColumnsToSubscriptionsTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'product_type',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Product Type'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'product_options',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '64k',
                'comment' => 'Product Options'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'product_sku',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Product SKU'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'item_price',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'comment' => 'Item Price'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'item_original_price',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Item Original Price'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'item_tax_percent',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'comment' => 'Item Tax Percent'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'item_tax_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'comment' => 'Item Tax Amount'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'item_discount_percent',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'comment' => 'Item Discount Percent'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'item_discount_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => '0.0000',
                'comment' => 'Item Discount Amount'
            ]
        );

        return $this;
    }

    /**
     * Add order payment data columns to subscriptions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addPaymentDataColumnsToSubscriptionTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'payment_method',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 128,
                'comment' => 'Payment Method'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'payment_cc_exp_month',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 12,
                'comment' => 'Payment Cc Exp Month'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'payment_cc_exp_year',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 12,
                'comment' => 'Payment Cc Exp Year'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'payment_cc_last_4',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 100,
                'comment' => 'Payment Cc Last 4'
            ]
        );

        $connection->addColumn(
            $installer->getTable('vantiv_subscriptions'),
            'payment_additional_information',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '64k',
                'comment' => 'Payment Additional Information'
            ]
        );

        return $this;
    }

    /**
     * Create subscription address table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionAddressTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_subscription_address'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_subscription_address')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Subscription Id'
        )->addColumn(
            'region_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Region Id'
        )->addColumn(
            'fax',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Fax'
        )->addColumn(
            'region',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Region'
        )->addColumn(
            'postcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Postcode'
        )->addColumn(
            'lastname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Lastname'
        )->addColumn(
            'street',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Street'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'City'
        )->addColumn(
            'email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Email'
        )->addColumn(
            'telephone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Phone Number'
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            2,
            [],
            'Country Id'
        )->addColumn(
            'firstname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Firstname'
        )->addColumn(
            'address_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Address Type'
        )->addColumn(
            'prefix',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Prefix'
        )->addColumn(
            'middlename',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Middlename'
        )->addColumn(
            'suffix',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Suffix'
        )->addColumn(
            'company',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Company'
        )->addForeignKey(
            $installer->getFkName(
                'vantiv_subscription_address',
                'subscription_id',
                'vantiv_subscriptions',
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable('vantiv_subscriptions'),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Subscription Address'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Convert bigint columns to varchar
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function convertBigintColumns(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $installer->getConnection()->changeColumn(
            $installer->getTable('vantiv_recurring_plans'),
            'litle_txn_id',
            'litle_txn_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 20,
                'nullable' => false,
                'comment' => 'Vantiv Transaction ID'
            ]
        );

        $installer->getConnection()->changeColumn(
            $installer->getTable('vantiv_subscriptions'),
            'vantiv_subscription_id',
            'vantiv_subscription_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 20,
                'nullable' => false,
                'comment' => 'Vantiv Subscription ID'
            ]
        );

        $installer->getConnection()->changeColumn(
            $installer->getTable('vantiv_certification_results'),
            'little_txn_id',
            'litle_txn_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 20,
                'nullable' => false,
                'comment' => 'Vantiv Transaction ID'
            ]
        );

        return $this;
    }

    /**
     * Create recovery transactions table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createRecoveryTransactionTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_recovery_transaction'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_recovery_transaction')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Subscription Id'
        )->addColumn(
            'litle_txn_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Transaction Id'
        )->addColumn(
            'report_group',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            25,
            ['nullable' => false],
            'Report Group'
        )->addColumn(
            'response_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3,
            ['nullable' => false],
            'Response Code'
        )->addColumn(
            'response_message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            512,
            [],
            'Response Message'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Status'
        )->addIndex(
            $installer->getIdxName('vantiv_recovery_transaction', ['litle_txn_id']),
            ['litle_txn_id']
        )->addForeignKey(
            $installer->getFkName(
                'vantiv_recovery_transaction',
                'subscription_id',
                'vantiv_subscriptions',
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable('vantiv_subscriptions'),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Recovery Transactions'
        );
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Add index on original_order_increment_id column of the vantiv_subscriptions table,
     * since it's used for subscription lookup as part of recovery transaction import
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addIndexOnOrderIncrIdToSubscrTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $installer->getConnection()->addIndex(
            $installer->getTable('vantiv_subscriptions'),
            $installer->getIdxName('vantiv_subscriptions', ['original_order_increment_id']),
            ['original_order_increment_id']
        );

        return $this;
    }

    /**
     * Create Vantiv data import processing date table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createImportProcessingDateTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_import_processing_date'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_import_processing_date')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'import_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'Import Code'
        )->addColumn(
            'merchant_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'Merchant Id'
        )->addColumn(
            'last_processed_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            ['nullable' => false],
            'Last Processed Date'
        )->setComment(
            'Vantiv Data Imports Processing Dates'
        );

        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create subscription amount changelog table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function createSubscriptionAmountChangelogTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Create table 'vantiv_import_processing_date'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('vantiv_subscription_amount_changelog')
        )->addColumn(
            'log_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Log Id'
        )->addColumn(
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Subscription Id'
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true],
            'Entity Id'
        )->addColumn(
            'entity_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => false],
            'Entity Type'
        )->addColumn(
            'amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Amount'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Updated At'
        )->addColumn(
            'start_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Start Date'
        )->addColumn(
            'end_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'End Date'
        )->addIndex(
            $installer->getIdxName('vantiv_subscription_amount_changelog', ['entity_id', 'entity_type']),
            ['entity_id', 'entity_type']
        )->addIndex(
            $installer->getIdxName(
                'vantiv_subscription_amount_changelog',
                ['subscription_id', 'updated_at', 'start_date', 'end_date']
            ),
            ['subscription_id', 'updated_at', 'start_date', 'end_date']
        )->addForeignKey(
            $installer->getFkName(
                'vantiv_subscription_amount_changelog',
                'subscription_id',
                'vantiv_subscriptions',
                'subscription_id'
            ),
            'subscription_id',
            $installer->getTable('vantiv_subscriptions'),
            'subscription_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Vantiv Subscription Amount Change Log'
        );

        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create is_system column in subscription addon table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addIsSystemColumnToAddonTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscription_addon'),
            'is_system',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'default' => '0',
                'comment' => 'Is System'
            ]
        );

        return $this;
    }

    /**
     * Create is_system column in subscription discount table
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    private function addIsSystemColumnToDiscountTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $connection = $installer->getConnection();

        $connection->addColumn(
            $installer->getTable('vantiv_subscription_discount'),
            'is_system',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'default' => '0',
                'comment' => 'Is System'
            ]
        );

        return $this;
    }
}
