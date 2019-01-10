<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 10:44 AM
 */

namespace SMG\Sap\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Create the desired tables below on the install of the module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // start the setup
        $setup->startSetup();

        // create the status table
        $this->createSalesOrderStatusSap($setup);

        // create the sap order table
        $this->createSalesOrderSap($setup);

        // create the sap order item table
        $this->createSalesOrderItemSap($setup);

        // create the sap order history table
        $this->createSalesOrderSapHistory($setup);

        // create the sap order item history table
        $this->createSalesOrderItemSapHistory($setup);

        // create the sap order batch table
        $this->createSalesOrderSapBatch($setup);

        // end the setup
        $setup->endSetup();
    }

    /**
     * Create the sales_order_status_sap table.
     * This table is used for the different status types
     * of an order according to SAP.
     *
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderStatusSap(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_status_sap';

        // make a new table with the desired table name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'status',
            Table::TYPE_TEXT,
            32,
            [
                'primary' => true,
                'nullable' => false
            ]
        );

        $table->addColumn(
            'label',
            Table::TYPE_TEXT,
            128,
            [
                'nullable' => false
            ]
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderSap(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap';

        // make a new table with the desired table name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'sap_order_id',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'order_created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addColumn(
            'sap_order_status',
            Table::TYPE_TEXT,
            1,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'order_status',
            Table::TYPE_TEXT,
            32,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'sap_billing_doc_number',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'sap_billing_doc_date',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'sap_payer_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => true,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_id',
                'sales_order',
                'entity_id'
            ),
            'order_id',
            $setup->getTable('sales_order'),
            'entity_id',
            Table::ACTION_RESTRICT
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_status',
                'sales_order_status_sap',
                'status'
            ),
            'order_status',
            $setup->getTable('sales_order_status_sap'),
            'status',
            Table::ACTION_RESTRICT
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderItemSap(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_item';

        // make a new table with the desired table name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_sap_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'sap_order_status',
            Table::TYPE_TEXT,
            1,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'order_status',
            Table::TYPE_TEXT,
            32,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'fulfillment_location',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'sku',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'sku_description',
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'qty',
            Table::TYPE_DECIMAL,
            '12,4',
            [
                'unsigned' => true,
                'nullable' => false
            ]
        );

        $table->addColumn(
            'confirmed_qty',
            Table::TYPE_DECIMAL,
            '12,4',
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'ship_tracking_number',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_sap_id',
                 'sales_order_sap',
                'entity_id'
            ),
            'order_sap_id',
            $setup->getTable('sales_order_sap'),
            'entity_id',
            Table::ACTION_RESTRICT
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_status',
                'sales_order_status_sap',
                'status'
            ),
            'order_status',
            $setup->getTable('sales_order_status_sap'),
            'status',
            Table::ACTION_RESTRICT
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderSapHistory(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_history';

        // make a new table with the desired table name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_sap_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_status',
            Table::TYPE_TEXT,
            32,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'order_status_notes',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_sap_id',
                'sales_order_sap',
                'entity_id'
            ),
            'order_sap_id',
            $setup->getTable('sales_order_sap'),
            'entity_id',
            Table::ACTION_RESTRICT
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_status',
                'sales_order_status_sap',
                'status'),
            'order_status',
            $setup->getTable('sales_order_status_sap'),
            'status',
            Table::ACTION_RESTRICT
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderItemSapHistory(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_item_history';

        // make a new table with the desired table name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_sap_item_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_status',
            Table::TYPE_TEXT,
            32,
            [
                'nullable' => false
            ]
        );

        $table->addColumn(
            'order_status_notes',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ]
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_sap_item_id',
                'sales_order_sap_item',
                'entity_id'
            ),
            'order_sap_item_id',
            $setup->getTable('sales_order_sap_item'),
            'entity_id',
            Table::ACTION_RESTRICT
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_status',
                'sales_order_status_sap',
                'status'),
            'order_status',
            $setup->getTable('sales_order_status_sap'),
            'status',
            Table::ACTION_RESTRICT
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderSapBatch(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_batch';

        // make a new table with the desired table name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'order_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
            ]
        );

        $table->addColumn(
            'is_capture',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default' => false
            ]
        );

        $table->addColumn(
            'capture_process_date',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'is_shipment',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default' => false
            ]
        );

        $table->addColumn(
            'shipment_process_date',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'is_unauthorized',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default' => false
            ]
        );

        $table->addColumn(
            'unauthorized_process_date',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true
            ]
        );

        $table->addForeignKey(
            $setup->getFkName(
                $tableName,
                'order_id',
                'sales_order',
                'entity_id'
            ),
            'order_id',
            $setup->getTable('sales_order'),
            'entity_id',
            Table::ACTION_RESTRICT
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }
}