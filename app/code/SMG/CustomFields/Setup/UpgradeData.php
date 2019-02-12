<?php
namespace SMG\Customfields\Setup;

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
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_volume',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Volume',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_volume_uom',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Volume UOM',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
				'apply_to' 	=> ''
			]
		);	
		
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'product_length',
			[
				'type' 		=> 'decimal',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Product Length',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
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
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
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
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
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
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
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
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
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
				'required' 	=> false,
				'user_defined' => false,
				'default' 	=> '',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
				'apply_to' 	=> ''
			]
		);	
		
		
 
	 }
	 
	 if ( version_compare($context->getVersion(), '1.0.2', '<' )) {
		 
		 
		  $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
		  
		   $entityTypeId = 4; // Find these in the eav_entity_type table
           $eavSetup->removeAttribute($entityTypeId, 'states_not_allowed');

		  
		  $eavSetup->addAttribute(
	             \Magento\Catalog\Model\Product::ENTITY,
	             'state_not_allowed', [
	            'type' => 'text',
	            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
	            'frontend' => '',
	             'label' => 'States shipping not allow',
	            'input' => 'multiselect',
	            'group' => 'Custom Fields',
	            'class' => 'shipping',
	            'source' => 'SMG\Customfields\Model\Config\Source\Regionoptions',	            
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'visible' =>    true,
	            'required' => false,
	            'user_defined' => false,
	            'default' => '',
	            'searchable' => false,
	            'filterable' => false,
	            'comparable' => false,
	            'visible_on_front' => false,
	            'used_in_product_listing' => true,
	            'unique' => false
	                ]
	    );
	    
	    
	    $eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
						'zip_not_allowed',
						[
							'group' => 'Custom Fields',
							'type' => 'text',
							'backend' => '',
							'frontend' => '',
							'label' => 'Zip Not Allow',
							'input' => 'textarea',
							'class' => '',
							'source' => '',
							'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
							'is_html_allowed_on_front' => true,
							'visible' => true,
							'required' => false,
							'user_defined' => false,
							'default' => '',
							'searchable' => false,
							'filterable' => false,
							'comparable' => false,
							'visible_on_front' => false,
							'used_in_product_listing' => true,
							'wysiwyg_enabled' => false,
							'unique' => false,
							'apply_to' => ''
						]
				);
				
				$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
						'county_not_allowed',
						[
							'group' => 'Custom Fields',
							'type' => 'text',
							'backend' => '',
							'frontend' => '',
							'label' => 'County Not Allow',
							'input' => 'textarea',
							'class' => '',
							'source' => '',
							'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
							'is_html_allowed_on_front' => true,
							'visible' => true,
							'required' => false,
							'user_defined' => false,
							'default' => '',
							'searchable' => false,
							'filterable' => false,
							'comparable' => false,
							'visible_on_front' => false,
							'used_in_product_listing' => true,
							'wysiwyg_enabled' => false,
							'unique' => false,
							'apply_to' => '' 
						]
				);
	 }
	         
	}
}
