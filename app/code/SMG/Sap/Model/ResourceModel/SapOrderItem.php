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
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\SapOrderStatusFactory;
use SMG\Sap\Model\ResourceModel\SapOrder;
use SMG\Sap\Model\ResourceModel\SapOrderStatus;
use SMG\Sap\Model\ResourceModel\SapOrderShipment\CollectionFactory as SapOrderShipmentCollectionFactory;

class SapOrderItem extends AbstractDb
{
    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    /**
     * @var SapOrder
     */
    protected $_sapOrder;

    /**
     * @var SapOrderStatusFactory
     */
    protected $_sapOrderStatusFactory;

    /**
     * @var SapOrderStatus
     */
    protected $_sapOrderStatus;

    /**
     * @var SapOrderShipmentCollectionFactory
     */
    protected $_sapOrderShipmentCollectionFactory;

    protected function _construct()
    {
        $this->_init('sales_order_sap_item', 'entity_id');
    }

    public function __construct(Context $context,
        SapOrderFactory $sapOrderFactory,
        SapOrder $sapOrder,
        SapOrderStatusFactory $sapOrderStatusFactory,
        SapOrderStatus $sapOrderStatus,
        SapOrderShipmentCollectionFactory $sapOrderShipmentCollectionFactory,
        $connectionName = null)
    {
        parent::__construct($context, $connectionName);

        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrder = $sapOrder;
        $this->_sapOrderStatusFactory = $sapOrderStatusFactory;
        $this->_sapOrderStatus = $sapOrderStatus;
        $this->_sapOrderShipmentCollectionFactory = $sapOrderShipmentCollectionFactory;
    }

    public function getSapOrder($orderSapId)
    {
        /**
         * @var \SMG\Sap\Model\SapOrder
         */
        $sapOrder = $this->_sapOrderFactory->create();

        // load the data for the order id
        $this->_sapOrder->load($sapOrder, $orderSapId);

        return $sapOrder;
    }

    public function getStatus($status)
    {
        /**
         * @var \SMG\Sap\Model\SapOrderStatus
         */
        $sapOrderStatus = $this->_sapOrderStatusFactory->create();

        // load the data for the order id
        $this->_sapOrderStatus->load($sapOrderStatus, $status);

        return $sapOrderStatus;
    }

    /**
     * Get the collection of the sap order shipments
     *
     * @param $orderSapItemId
     * @return SapOrderShipment\Collection
     */
    public function getSapOrderShipments($orderSapItemId)
    {
        /**
         * @var \SMG\Sap\Model\ResourceModel\SapOrderShipment\Collection $sapOrderShipments
         */
        $sapOrderShipments = $this->_sapOrderShipmentCollectionFactory->create();
        $sapOrderShipments->addFieldToFilter('order_sap_item_id', ['eq' => $orderSapItemId]);

        return $sapOrderShipments;
    }
}