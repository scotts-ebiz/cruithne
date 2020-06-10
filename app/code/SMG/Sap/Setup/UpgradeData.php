<?php

namespace SMG\Sap\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use SMG\Sap\Model\ResourceModel\SapOrder\CollectionFactory as SapOrderCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var SapOrderCollectionFactory
     */
    protected $_sapOrderCollectionFactory;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;


    public function __construct(SapOrderCollectionFactory $sapOrderCollectionFactory,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory)
    {
        $this->_sapOrderCollectionFactory = $sapOrderCollectionFactory;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<'))
        {
            $this->addDataVersion110($setup);
        }

        // Add values to sales_order_status_sap and moved data from
        // sales_order_sap and sales_order_sap_item to sales_order_sap_shipment
        if (version_compare($context->getVersion(), '3.0.0', '<'))
        {
            $this->addDataVersion300($setup);
        }

        if (version_compare($context->getVersion(), '3.3.0', '<'))
        {
            $this->addDataVersion330($setup);
        }
    }

    /**
     * Add Data for Version 1.1.0 that was missed previously
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion110(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('sales_order_status_sap');

        // create the data
        $data = [
            ['status' => 'updated', 'label' => 'Updated']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 3.0.0
     *
     * - Added 2 new values for the sales_order_status_sapj
     * - Moved Data from existing tables to the newly created table for shipment data
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion300(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('sales_order_status_sap');

        // create the data
        $data = [
            ['status' => 'capture_failed', 'label' => 'Capture Failed - Reverse Authorization Processing'],
            ['status' => 'order_canceled', 'label' => 'Order Canceled - Authorization Voided'],
            ['status' => 'order_captured', 'label' => 'Order Captured']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Add Data for Version 3.3.0
     *
     * - Added 1 new value for the sales_order_status_sap
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion330(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('sales_order_status_sap');

        // create the data
        $data = [
            ['status' => 'order_partially_shipped', 'label' => 'Order Partially Shipped'],
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}