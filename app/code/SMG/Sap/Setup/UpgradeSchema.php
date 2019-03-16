<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 10:44 AM
 */

namespace SMG\Sap\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<'))
        {
            $this->updateColumnVersion110($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<'))
        {
            $this->updateColumnVersion120($setup);
        }

        if (version_compare($context->getVersion(), '1.3.0', '<'))
        {
            $this->updateColumnVersion130($setup);
        }
    }

    private function updateColumnVersion110(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'sales_order_sap';

        // make a new table with the desired table name
        $setup->getConnection()->changeColumn(
            $tableName,
            'sap_billing_doc_date',
            'sap_billing_doc_date',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true
            ]
        );

        // end the setup
        $setup->endSetup();
    }

    private function updateColumnVersion120(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'sales_order_sap';

        // make a new table with the desired table name
        $setup->getConnection()->addColumn(
            $tableName,
            'delivery_number',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'New SAP field for delivery information'
            ]
        );

        // end the setup
        $setup->endSetup();
    }

    private function updateColumnVersion130(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        // update batch table
        $this->updateSalesOrderSapBatch130($setup);

        // create the new batch item table
        $this->createSalesOrderSapBatchItem($setup);

        // end the setup
        $setup->endSetup();
    }

    private function updateSalesOrderSapBatch130(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_batch';

        // make a new table with the desired table name
        $setup->getConnection()->addColumn(
            $tableName,
            'is_order',
            [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => false,
                'comment' => 'Flag for when and order has been sent to SAP'
            ]
        );

        $setup->getConnection()->addColumn(
            $tableName,
            'order_process_date',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'comment' => 'Datetime for when an order was sent to SAP'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSalesOrderSapBatchItem(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_batch_item';

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
            'creditmemo_order_id',
            Table::TYPE_INTEGER,
            null,
            [
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
            'order_item_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
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
            'is_credit',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default' => false
            ]
        );

        $table->addColumn(
            'credit_process_date',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true
            ]
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }
}