<?php

namespace SMG\OrderDiscount\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // being setup
        $setup->startSetup();

        $tableName = 'smg_order_discount';

        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'unsigned' => true,
                'nullable' => false
            ]
        );
 
        $table->addColumn(
            'discount_title',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'discount_values',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'discount_type',
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => false
            ]
        );

        // create the table
        $setup->getConnection()->createTable($table);

        // end setup
        $setup->endSetup();
    }
}
