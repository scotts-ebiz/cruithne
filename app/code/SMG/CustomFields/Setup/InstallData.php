<?php
/* app/code/Custom/Attribute/Setup/InstallData.php */

namespace SMG\CustomFields\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
	}
	
	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'smg_sku',
			[
				'type' 		=> 'text',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'SMG Sku',
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
			'drupalproductid',
			[
				'type' 		=> 'int',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'Drupal ProducID',
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
				'apply_to' => ''
			]
		);
		$eavSetup->addAttribute(
                 \Magento\Catalog\Model\Product::ENTITY,
                'enable_rma', [
				'type' => 'int',
                'label' => 'Enable RMA',
                'input' => 'boolean',                
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',                
                'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => null,
                'group' => 'Custom Fields',
                'backend' => ''
				 ]
        );
        
        		$eavSetup->addAttribute(
                 \Magento\Catalog\Model\Product::ENTITY,
                'in_feed', [
				'type' => 'int',
                'label' => 'In Feed',
                'input' => 'boolean',                
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',                
                'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => null,
                'group' => 'Custom Fields',
                'backend' => ''
				 ]
              );
         
        $eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'smg_uom',
			[
				'type' 		=> 'text',
				'backend' 	=> '',
				'frontend' 	=> '',
				'label' 	=> 'smg_uom',
				'input' 	=> 'text',
				'class' 	=> '',
				'source' 	=> '',
				'global' 	=> \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' 	=> true,
				'required' 	=> false,
				'user_defined' => true,
				'default' 	=> 'ea',
				'searchable'=> false,
				'filterable'=> false,
				'comparable'=> false,
				'visible_on_front' => true,
				'used_in_product_listing' => true,
				'unique' 	=> false,
				'group' => 'Custom Fields',
				'apply_to' 	=> ''
			]
		); 
         
         $eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
						'video_html',
						[
							'group' => 'Custom Fields',
							'type' => 'text',
							'backend' => '',
							'frontend' => '',
							'label' => 'Youtube Video HTML',
							'input' => 'textarea',
							'class' => '',
							'source' => '',
							'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
							'wysiwyg_enabled' => true,
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
							'wysiwyg_enabled' => true,
							'unique' => false,
							'apply_to' => ''
						]
				);
         
        $eavSetup->addAttribute(
	             \Magento\Catalog\Model\Product::ENTITY,
	             'states_not_allowed', [
	            'type' => 'int',
	            'backend' => '',
	            'frontend' => '',
	             'label' => 'States shipping not allow',
	            'input' => 'select',
	            'group' => 'Custom Fields',
	            'class' => 'shipping',
	            'source' => 'Magento\Customer\Model\ResourceModel\Address\Attribute\Source\Region',	            
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'visible' =>    true,
	            'required' => false,
	            'user_defined' => false,
	            'default' => '',
	            'searchable' => false,
	            'filterable' => true,
	            'comparable' => false,
	            'visible_on_front' => true,
	            'used_in_product_listing' => true,
	            'unique' => false
	                ]
	    );
	    
	    $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'product_sizes',/* Custom Attribute Code */
            [
                'type' => 'int',/* Data type in which formate your value save in database*/
                'backend' => '',
                'frontend' => '',
                'label' => 'Size', /* lablel of your attribute*/
                'input' => 'select',
                'class' => '',
                'source' => 'SMG\CustomFields\Model\Config\Source\Options',
                                /* Source of your select type custom attribute options*/
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                                    /*Scope of your attribute */
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'group' => 'Custom Fields', 
                'unique' => false
            ]
        );

	}
}
