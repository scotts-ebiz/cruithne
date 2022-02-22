<?php

namespace MiniOrange\SP\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MiniOrange\SP\Helper\SPConstants;

/**
 * This class is the setup install script that adds 2 columns
 * to the Admin Table so that we can store and retrieve certain
 * values. This is run just once during installation of the module
 *
 * <b>NOTE:</b> To re-run this script - Go to setup_module table and
 *       delete the entry for the module 'MiniOrange_SP' and run the
 *       upgrade command.
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * The main install function that is called to run our scripts
     *
     * @param SchemaSetupInterface
     * @param ModuleContextInterface
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $tableName = $setup->getTable('admin_user');
        $connection = $setup->getConnection();
        // create admin columns
        if ($connection->isTableExists($tableName) == true) {
            $connection->addColumn(
                $tableName,
                // add session index to the admin table
                SPConstants::SESSION_INDEX,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true, 'default' => '',
                    'comment' => 'Session Index column'
                ]
            );
            $connection->addColumn(
                $tableName,
                // add name id to the admin table
                SPConstants::NAME_ID,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true, 'default' => '',
                    'comment' => 'NameId column'
                ]
            );
        }
        $setup->endSetup();
    }
}
