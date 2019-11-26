<?php
namespace SMG\SubscriptionApi\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
	private $_eavSetupFactory;

	public function __construct(
		EavSetupFactory $eavSetupFactory
	)
	{
		$this->_eavSetupFactory = $eavSetupFactory;
	}
	
	public function install(
		ModuleDataSetupInterface $setup,
		ModuleContextInterface $context
	)
	{
		$eavSetup = $this->_eavSetupFactory->create( [ 'setup' => $setup ] );

		// Add "addon attribute"
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

	}
}
