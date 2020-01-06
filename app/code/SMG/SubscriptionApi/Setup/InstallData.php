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

        $installer->endSetup();

	}
}
