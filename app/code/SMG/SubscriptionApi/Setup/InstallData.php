<?php
namespace SMG\SubscriptionApi\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order;

class InstallData implements InstallDataInterface
{
	private $_eavSetupFactory;
	private $_customerSetupFactory;
    private $_salesSetupFactory;

	public function __construct(
		EavSetupFactory $eavSetupFactory,
		\Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory
	)
	{
		$this->_eavSetupFactory = $eavSetupFactory;
		$this->_customerSetupFactory = $customerSetupFactory;
        $this->_salesSetupFactory = $salesSetupFactory;
	}
	
	public function install(
		ModuleDataSetupInterface $setup,
		ModuleContextInterface $context
	)
	{
		$installer = $setup;
        $installer->startSetup();

        // Add attributes to the orders
        $salesSetup = $this->_salesSetupFactory->create( [
            'resourceName'  => 'sales_setup',
            'setup'         => $setup
        ] );

        $salesSetup->addAttribute(Order::ENTITY, 'gigya_id', [
            'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length'    => 255,
            'visible'   => true,
            'nullable'  => false,
        ] );
        $salesSetup->addAttribute(Order::ENTITY, 'master_subscription_id', [
            'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length'    => 255,
            'visible'   => true,
            'nullable'  => false,
        ] );
        $salesSetup->addAttribute(Order::ENTITY, 'subscription_id', [
            'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length'    => 255,
            'visible'   => true,
            'nullable'  => false,
        ] );
        $salesSetup->addAttribute(Order::ENTITY, 'subscription_type', [
            'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length'    => 255,
            'visible'   => true,
            'nullable'  => false,
        ] );
        $salesSetup->addAttribute(Order::ENTITY, 'subscription_addon', [
            'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            'visible'   => true,
            'nullable'  => false,
        ] );
        $salesSetup->addAttribute(Order::ENTITY, 'ship_date', [
            'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            'visible'   => true,
            'nullable'  => false,
        ] );

        // Add "Recurly Account Code" attribute to customers
        $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'recurly_account_code', [
            'type' => 'varchar',
            'label' => 'Recurly Account Code',
            'input' => 'text',
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 500,
            'system' => false,
            'backend' => ''
        ]);
        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'recurly_account_code')
        ->addData(['used_in_forms' => [
                'adminhtml_customer',
            ]
        ]);
        $attribute->save();

		// Add "Is Addon" attribute to products
		$eavSetup = $this->_eavSetupFactory->create( [ 'setup' => $setup ] );
		$eavSetup->addAttribute(
             \Magento\Catalog\Model\Product::ENTITY,
            'is_addon', [
				'type' 			=> 'int',
	            'label' 		=> 'Is Addon Product?',
	            'input' 		=> 'boolean',                
	            'source' 		=> 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',                
	            'global' 		=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'visible' 		=> true,
	            'required' 		=> false,
	            'user_defined' 	=> false,
	            'default' 		=> null,
	            'group' 		=> 'Custom Fields',
	            'backend' 		=> ''
			 ]
        );

        $installer->endSetup();

	}
}
