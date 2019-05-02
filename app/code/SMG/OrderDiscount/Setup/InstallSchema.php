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
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $tableName = 'smg_order_discount';

        $table = $connection->newTable($setup->getTable($tableName));

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
            'disc_cond_code',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'magento_rule_type',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'application_type',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        // create the table
        $connection->createTable($table);
      
        if ($connection->tableColumnExists('sales_order', 'disc_cond_code') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'disc_cond_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'nullable' => true,
                        'comment' => 'Discount condition code'
                    ]
                );
        }
        
        if ($connection->tableColumnExists('sales_order', 'disc_fixed_amt') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'disc_fixed_amt',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'nullable' => true,
                        'comment' => 'Discount Fixed Amount'
                    ]
                );
        }
        
        if ($connection->tableColumnExists('sales_order', 'disc_perc_amt') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'disc_perc_amt',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'nullable' => true,
                        'comment' => 'Discount Percentage Amount'
                    ]
                );
        }

       
       

        // end setup
        $setup->endSetup();
    }
}
