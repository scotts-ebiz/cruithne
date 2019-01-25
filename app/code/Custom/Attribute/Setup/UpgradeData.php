<?php
namespace Custom\Attribute\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
	}
	
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		if ( version_compare($context->getVersion(), '1.0.1', '<' )) {
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_weight_uom',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Weight UOM',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_volumn',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Volumn',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_volumn_uom',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Volumn UOM',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_lenght',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Lenght',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_width',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Width',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_height',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Height',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_dimension_uom',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Dimension UOM',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_zz_brand',
			[
				'type' 		=> 'text',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product zz Brand',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_zz_subbrand',
			[
				'type' 		=> 'text',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product zz SubBrand',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> true,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'General Custom',
				'apply_to' 	=> ''
			]
		);	
		
		
 
	 }        
	}
}
