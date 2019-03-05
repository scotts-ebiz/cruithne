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

        $tableName = 'smg_discount_codes';

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
            'magento_desc',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'magento_coupon_code',
            Table::TYPE_TEXT,
            255,
            [
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
            'disc_fixed_amt',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ]
        );
        
        $table->addColumn(
            'disc_perc_amt',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ]
        );
        
        $table->addColumn(
            'discount_title',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'discount_values',
            Table::TYPE_TEXT,
            255,
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
        /*$setup->getConnection()->createTable($table);


       
       $eavTable = $setup->getTable('quote_item');

        $disc_cond_code = [
            'disc_cond_code' => [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'disc_cond_code',
            ],

        ];
        
        $disc_fixed_amt = [
            'disc_fixed_amt' => [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'disc_fixed_amt',
            ],

        ];

         $disc_perc_amt = [
            'disc_perc_amt' => [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'disc_perc_amt',
            ],

        ];

        $connection = $setup->getConnection();
        foreach ($disc_cond_code as $name => $definition) {
        $connection->addColumn($eavTable, $name, $definition);
        }
        foreach ($disc_fixed_amt as $name => $definition) {
        $connection->addColumn($eavTable, $name, $definition);
        }
        foreach ($disc_perc_amt as $name => $definition) {
        $connection->addColumn($eavTable, $name, $definition);
        }

        // end setup
        $setup->endSetup(); */
    }
}

