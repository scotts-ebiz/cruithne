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
            'MagentoDescription',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'MagentoCouponCode',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'DiscCondCode',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );
        
        $table->addColumn(
            'DiscFixedAmt',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ]
        );
        
        $table->addColumn(
            'SAPDiscPercAmt',
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
        $setup->getConnection()->createTable($table);


       
       $eavTable = $setup->getTable('quote_item');

        $custom_discount_label = [
            'custom_discount_label' => [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'custom_discount_label',
            ],

        ];
        
        $custom_discount_value = [
            'custom_discount_value' => [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'custom_discount_value',
            ],

        ];

        $connection = $setup->getConnection();
        foreach ($custom_discount_label as $name => $definition) {
        $connection->addColumn($eavTable, $name, $definition);
        }
        foreach ($custom_discount_value as $name => $definition) {
        $connection->addColumn($eavTable, $name, $definition);
        }

        // end setup
        $setup->endSetup();
    }
}

