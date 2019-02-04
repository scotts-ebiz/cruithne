<?php
namespace Creditvantiv\Payment\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $conn           = $setup->getConnection();
        $first_table    = $setup->getTable('sales_order_credit_batch');
        $second_table   = $setup->getTable('sales_order_credit_batch_history');
        $table = $conn->newTable($first_table)
                        ->addColumn(
                            'entity_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['identity'=>true,'unsigned'=>true,'nullable'=>false,'primary'=>true]
                            )
                        ->addColumn(
                            'order_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['nullable'=>false],
                            'Order ID'
                            )
                         ->addColumn(
                            'credit_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['nullable'=>false],
                            'Credit ID'
                            )
                        ->addColumn(
                            'is_capture',
                            Table::TYPE_BOOLEAN,
                            null,
                            ['nullable'=>false]
                            )
                        ->addColumn(
                            'capture_process_date',
                            Table::TYPE_DATETIME,
                            null,
                            ['nullable'=>false]
                            )
                        ->addColumn(
                            'is_shipment',
                            Table::TYPE_BOOLEAN,
                            null,
                            ['nullable'=>false]
                            )
                        ->addColumn(
                            'shipment_process_date',
                            Table::TYPE_DATETIME,
                            255,
                            ['nullable'=>false]
                            )
                        ->addColumn(
                            'credit_note',
                            Table::TYPE_TEXT,
                            null,
                            ['nullable'=>false]
                            )
                        ->addColumn(
                            'credit_amount',
                            Table::TYPE_FLOAT,
                            null,
                            ['nullable'=>false]
                            )
                        ->addColumn(
                            'status',
                            Table::TYPE_TEXT,
                            null,
                            ['nullable'=>false]
                            )
                        ->setOption('charset','utf8');
        $conn->createTable($table);
    
        $table = $conn->newTable($second_table)
                         ->addColumn(
                                'response_id',
                                Table::TYPE_INTEGER,
                                null,
                                ['identity'=>true,'unsigned'=>true,'nullable'=>false,'primary'=>true]
                                )
                            ->addColumn(
                                'entity_id',
                                Table::TYPE_INTEGER,
                                null,
                                ['nullable'=>false]
                                )
                            ->addColumn(
                                'order_id',
                                Table::TYPE_INTEGER,
                                null,
                                ['nullable'=>false]
                                )
                            ->addColumn(
                                'response_text',
                                Table::TYPE_TEXT,
                                null,
                                ['nullable'=>false]
                                )
                            ->setOption('charset','utf8');
        $conn->createTable($table);

        $installer->endSetup();
    }
}