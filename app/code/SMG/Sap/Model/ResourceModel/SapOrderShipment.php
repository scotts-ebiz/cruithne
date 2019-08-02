<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 2:42 PM
 */

namespace SMG\Sap\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;

class SapOrderShipment extends AbstractDb
{
    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    protected function _construct()
    {
        $this->_init('sales_order_sap_shipment', 'entity_id');
    }

    public function __construct(Context $context,
        SapOrderResource $sapOrderResource,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        $connectionName = null)
    {
        parent::__construct($context, $connectionName);

        $this->_sapOrderResource = $sapOrderResource;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
    }

    /**
     * Get the SapOrderItems for the desired orderId
     *
     * @param $orderId
     * @return null|SapOrderItem\Collection
     */
    public function getSapOrderItems($orderId)
    {
        // initialize the return value
        $sapOrderItems = null;

        // get the sap order
        $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);

        // make sure that it is set
        if (isset($sapOrder))
        {
            // filter for the desired order item
            $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
            $sapOrderItems->addFieldToFilter('order_sap_id', $sapOrder->getData('entity_id'));
        }

        // return
        return $sapOrderItems;
    }

    /**
     * Get the desired order Sap item from the table
     * for the desired order id and order sap item id
     *
     * @param $orderId
     * @param $orderSapItemId
     * @return null|\SMG\Sap\Model\SapOrderItem
     */
    public function getSapOrderItem($orderId, $orderSapItemId)
    {
        // initialize the return value
        $sapOrderItem = null;

        // get the list of order items
        $sapOrderItems = $this->getSapOrderItems($orderId);

        // if it is sort then filter for the desired sap item id
        if (isset($sapOrderItems))
        {
            // get the list of items for the desired sap item it
            $sapOrderItems->addFieldToFilter('entity_id', $orderSapItemId);

            // there should only be one but lets make sure
            /**
             * @var \SMG\Sap\Model\SapOrderItem $value
             */
            foreach($sapOrderItems as $value)
            {
                if (isset($value))
                {
                    $sapOrderItem = $value;
                    break;
                }
            }
        }

        // return
        return $sapOrderItem;
    }
}