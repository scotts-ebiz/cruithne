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

        // update the sales_order_sap_shipment table
        $tableName = $setup->getTable('sales_order_sap_shipment');

        // get the desired data to insert
        $data = $this->getSalesOrderSapShipmentData();

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * Get the array of values that need to be inserted into the sales_order_sap_shipment table
     *
     * @return array
     */
    private function getSalesOrderSapShipmentData()
    {
        // initialize the return
        $data = array();

        // get the list of sap orders that currently exists
        $sapOrders = $this->_sapOrderCollectionFactory->create();

        // loop through the list of sap orders that currently exists
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        foreach ($sapOrders as $sapOrder)
        {
            // get the sap order id and the delivery number for later use
            $orderSapId = $sapOrder->getData('entity_id');
            $deliveryNumber = $sapOrder->getData('delivery_number');

            // get the list of sap order items for the desired sap order
            $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
            $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $orderSapId]);
            $sapOrderItems->addFieldToFilter('ship_tracking_number', ['notnull' => true]);

            // loop through the list of sap order items that currently exists for the
            // given order sap id
            /**
             * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
             */
            foreach ($sapOrderItems as $sapOrderItem)
            {
                // get the desired values to add to the array
                $orderSapItemId = $sapOrderItem->getData('entity_id');
                $shipTrackingNumber = $sapOrderItem->getData('ship_tracking_number');
                $qty = $sapOrderItem->getData('qty');
                $confirmedQty = $sapOrderItem->getData('confirmed_qty');
                $fulfillmentLocation = $sapOrderItem->getData('fulfillment_location');
                $sapBillingDocNumber = $sapOrderItem->getData('sap_billing_doc_number');
                $sapBillingDocDate = $sapOrderItem->getData('sap_billing_doc_date');

                $data[] = array(
                    'order_sap_item_id' => $orderSapItemId,
                    'ship_tracking_number' => $shipTrackingNumber,
                    'qty' => $qty,
                    'confirmed_qty' => $confirmedQty,
                    'delivery_number' => $deliveryNumber,
                    'fulfillment_location' => $fulfillmentLocation,
                    'sap_billing_doc_number' => $sapBillingDocNumber,
                    'sap_billing_doc_date' => $sapBillingDocDate,
                );
            }
        }

        // return the array of values
        return $data;
    }
}