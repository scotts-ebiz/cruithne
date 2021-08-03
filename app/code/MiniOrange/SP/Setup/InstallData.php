<?php

namespace MiniOrange\SP\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use MiniOrange\SP\Helper\SPConstants;

/**
 * This class is the setup install script that adds 2 attributes
 * to the customer Table so that we can store and retrieve certain
 * values. This is run just once during installation of the module
 *
 * <b>NOTE:</b> To re-run this script - Go to setup_module table and
 *       delete the entry for the module 'MiniOrange_SP' and run the
 *       upgrade command.
 */
class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;
    
    
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }


    /**
     * The main install function that is called to run our scripts
     *
     * @param ModuleDataSetupInterface
     * @param ModuleContextInterface
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //create customer attributes
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            // add session index to the customer attribute
            SPConstants::SESSION_INDEX,
            [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Saml Session Index',
                'required' => false,
                'user_defined' => true,
                'visible' => false,
                'visible_on_front' => false,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'note' => 'Session Index column',
                'group' => 'General Information'
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            // add nameid to the customer attribute
            SPConstants::NAME_ID,
            [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Name ID',
                'required' => false,
                'user_defined' => true,
                'visible' => false,
                'visible_on_front' => false,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'note' => 'NameId column',
                'group' => 'General Information'
            ]
        );
        $setup->endSetup();
    }
}
