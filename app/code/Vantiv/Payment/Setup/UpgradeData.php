<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Setup;

use Magento\Customer\Setup\CustomerSetup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Vantiv\Payment\Helper\Recurring as RecurringHelper;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * UpgradeData constructor
     *
     * @param CategorySetupFactory $categorySetupFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        CategorySetupFactory $categorySetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Catalog\Setup\CategorySetup $catalogSetup */
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $groupName = 'Subscriptions';
            $catalogSetup->addAttributeGroup(Product::ENTITY, 'Default', $groupName, 16);

            $catalogSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'vantiv_recurring_enabled',
                [
                    'group' => $groupName,
                    'type' => 'int',
                    'frontend' => '',
                    'label' => 'Enabled',
                    'input' => 'boolean',
                    'class' => '',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO,
                    'apply_to' => implode(',', [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL]),
                    'visible_on_front' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'used_in_product_listing' => true
                ]
            );

            $catalogSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'vantiv_recurring_allow_start',
                [
                    'group' => $groupName,
                    'type' => 'int',
                    'frontend' => '',
                    'label' => 'Allow Selectable Start Date',
                    'input' => 'boolean',
                    'class' => '',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO,
                    'apply_to' => implode(',', [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL]),
                    'visible_on_front' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.8', '<')) {
            $setup->getConnection()->insert(
                $setup->getTable('sales_order_status'),
                [
                    'status' => RecurringHelper::PENDING_RECURRING_PAYMENT_ORDER_STATUS,
                    'label' => 'Pending Recurring Payment'
                ]
            );
            $setup->getConnection()->insert(
                $setup->getTable('sales_order_status_state'),
                [
                    'status' => RecurringHelper::PENDING_RECURRING_PAYMENT_ORDER_STATUS,
                    'state' => \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
                    'is_default' => 0,
                    'visible_on_front' => 1
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.9', '<')) {
            $setup->startSetup();

            /** @var CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'affluence',
                [
                    'type' => 'varchar',
                    'label' => 'Affluence Data',
                    'input' => 'hidden',
                    'required' => false,
                    'sort_order' => 150,
                    'visible' => true,
                    'system' => false,
                ]
            );

            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            $eavSetup->updateAttribute(Customer::ENTITY, 'affluence', 'is_used_for_customer_segment', '1');

            $setup->endSetup();
        }
    }
}
