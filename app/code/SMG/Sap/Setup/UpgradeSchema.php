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
use SMG\Sap\Model\ResourceModel\SapOrder\CollectionFactory as SapOrderCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem as SapOrderItemResource;


class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var SapOrderCollectionFactory
     */
    protected $_sapOrderCollectionFactory;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    /**
     * @var SapOrderItemResource
     */
    protected $_sapOrderItemResource;

    /**
     * UpgradeSchema constructor.
     *
     * @param SapOrderCollectionFactory $sapOrderCollectionFactory
     * @param SapOrderItemCollectionFactory $sapOrderItemCollectionFactory
     * @param SapOrderItemResource $sapOrderItemResource
     */
    public function __construct(SapOrderCollectionFactory $sapOrderCollectionFactory,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        SapOrderItemResource $sapOrderItemResource)
    {
        $this->_sapOrderCollectionFactory = $sapOrderCollectionFactory;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
        $this->_sapOrderItemResource = $sapOrderItemResource;
    }

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
        
        if (version_compare($context->getVersion(), '1.5.0', '<'))
        {
            $this->updateColumnVersion150($setup);
        }

        if (version_compare($context->getVersion(), '1.6.0', '<'))
        {
            $this->updateColumnVersion160($setup);
        }
        if (version_compare($context->getVersion(), '1.7.0', '<'))
        {
            $this->updateColumnVersion170($setup);
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
                'comment' => 'Datetime for when an cd order was sent to SAP'
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

    /**
     * ECOM-658 - required moving the invoice fields (sap_billing_doc_number
     * and sap_billing_doc_date) to the item level.
     *
     * This upgrade version does that change
     *
     * @param SchemaSetupInterface $setup
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateColumnVersion150(SchemaSetupInterface $setup)
    {
        // start the setup
        // add the two new columns to the sap item table
        $setup->startSetup();

        $tableName = 'sales_order_sap_item';

        $setup->getConnection()->addColumn(
            $tableName,
            'sap_billing_doc_number',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'size' => 10,
                'comment' => 'SAP Invoice Number'
            ]
        );

        $setup->getConnection()->addColumn(
            $tableName,
            'sap_billing_doc_date',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'default' => Table::TIMESTAMP_INIT_UPDATE,
                'comment' => 'SAP Invoice Date'
            ]
        );

        // end the setup
        $setup->endSetup();

        // add values to the new fields from sap order table
        $this->addInvoiceData();

        // start the setup
        // drop the two columns from the sap table as they have moved to the sap item table
        $setup->startSetup();

        $tableName = 'sales_order_sap';

        $setup->getConnection()->dropColumn($setup->getTable($tableName), 'sap_billing_doc_number');
        $setup->getConnection()->dropColumn($setup->getTable($tableName), 'sap_billing_doc_date');

        // end the setup
        $setup->endSetup();
    }

    /**
     * Adds the invoice number and invoice date to the new fields in sap order item
     * from the original fields in sap order
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function addInvoiceData()
    {
        // populate the table with values from the sap order table
        $sapOrders = $this->_sapOrderCollectionFactory->create();

        /**
         * @var \SMG\Sap\Model\Model\SapOrder $sapOrder
         */
        foreach ($sapOrders as $sapOrder)
        {
            $orderSapId = $sapOrder->getId();
            $invoiceNumber = $sapOrder->getData('sap_billing_doc_number');
            $invoiceDate = $sapOrder->getData('sap_billing_doc_date');

            // load the item data for the order sap id
            $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
            $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $orderSapId]);
            if (isset($sapOrderItems))
            {
                foreach ($sapOrderItems as $sapOrderItem)
                {
                    $sapOrderItem->setData('sap_billing_doc_number', $invoiceNumber);
                    $sapOrderItem->setData('sap_billing_doc_date', $invoiceDate);

                    // save to the database
                    $this->_sapOrderItemResource->save($sapOrderItem);
                }
            }
        }
    }

    /**
     * ECOM-106: Added reconciliation batch processing fields to the
     * SAP order batch processing table.
     *
     * @param SchemaSetupInterface $setup
     */
    private function updateColumnVersion160(SchemaSetupInterface $setup)
    {
        // start the setup
        // add new fields to the batch table
        $setup->startSetup();

        $tableName = 'sales_order_sap_batch';

        // make a new table with the desired table name
        $setup->getConnection()->addColumn(
            $tableName,
            'is_invoice_reconciliation',
            [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => false,
                'comment' => 'Flag for when and order is ready for reconciliation'
            ]
        );

        $setup->getConnection()->addColumn(
            $tableName,
            'invoice_reconciliation_date',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'comment' => 'Datetime for when an order has been reconciled'
            ]
        );

        // end the setup
        $setup->endSetup();

        // start the setup
        // rename the table
        $setup->startSetup();

        $tableNameOld = 'sales_order_sap_batch_item';
        $tableNameNew = 'sales_order_sap_batch_creditmemo';

        $setup->getConnection()->renameTable(
            $tableNameOld,
            $tableNameNew
        );

        // end the setup
        $setup->endSetup();
    }

    private function updateColumnVersion170(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        // create batch table
        $this->createSalesOrderSapBatchRma($setup);

        // end the setup
        $setup->endSetup();
    }

    private function createSalesOrderSapBatchRma(SchemaSetupInterface $setup)
    {
        $tableName = 'sales_order_sap_batch_rma';

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
            'rma_id',
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
            'is_return',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default' => false
            ]
        );

        $table->addColumn(
            'return_process_date',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true
            ]
        );

        $table->addColumn(
            'reason_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true
            ]
        );

        // create the table
        $setup->getConnection()->createTable($table);
    }
}
