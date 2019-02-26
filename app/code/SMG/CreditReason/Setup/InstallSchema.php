<?php

namespace SMG\CreditReason\Setup;

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

        $tableName = 'credit_reason_code';

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
            'reason_code',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'short_desc',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'long_desc',
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'is_active',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default' => true
            ]
        );

        // create the table
        $setup->getConnection()->createTable($table);

        // end setup
        $setup->endSetup();
    }
}