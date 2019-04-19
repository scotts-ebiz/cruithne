<?php

namespace SMG\OrderReason\Setup;

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
      
        if ($connection->tableColumnExists('sales_order_item', 'reason_code') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order_item'),
                    'reason_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'nullable' => true,
                        'comment' => 'Reason code'
                    ]
                );
        }
        
        if ($connection->tableColumnExists('sales_order_sap_item', 'reason_code') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order_sap_item'),
                    'reason_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'nullable' => true,
                        'comment' => 'Reason code'
                    ]
                );
        }
        
         // end setup
        $setup->endSetup();
    }
}
