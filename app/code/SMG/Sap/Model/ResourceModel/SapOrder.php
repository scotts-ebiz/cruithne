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
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder\CollectionFactory as SapOrderCollectionFactory;

class SapOrder extends AbstractDb
{
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Order
     */
    protected $_orderResource;

    /**
     * @var SapOrderCollectionFactory
     */
    protected $_sapOrderCollectionFactory;

    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    protected function _construct()
    {
        $this->_init('sales_order_sap', 'entity_id');
    }

    public function __construct(Context $context,
        OrderFactory $orderFactory,
        Order $orderResource,
        SapOrderCollectionFactory $sapOrderCollectionFactory,
        SapOrderFactory $sapOrderFactory,
        $connectionName = null)
    {
        parent::__construct($context, $connectionName);

        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_sapOrderCollectionFactory = $sapOrderCollectionFactory;
        $this->_sapOrderFactory = $sapOrderFactory;
    }

    /**
     * Return the order from the Order Id
     *
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder($orderId)
    {
        /**
         * @var \Magento\Sales\Model\Order
         */
        $order = $this->_orderFactory->create();

        // load the data for the order id
        $this->_orderResource->load($order, $orderId);

        return $order;
    }

    /**
     * Return the order from the Order Id
     *
     * @param $incrementId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderByIncrementId($incrementId)
    {
        /**
         * @var \Magento\Sales\Model\Order
         */
        $order = $this->_orderFactory->create();

        // load the data for the order id
        $this->_orderResource->load($order, $incrementId, 'increment_id');

        return $order;
    }

    /**
     * Get the sales_order_sap record for the provided
     * orderId
     *
     * @param $orderId
     * @return \SMG\Sap\Model\SapOrder
     */
    public function getSapOrderByOrderId($orderId)
    {
        // create a new sapOrder
        /**
         * @var \SMG\Sap\Model\SapOrder
         */
        $order = $this->_sapOrderFactory->create();

        if (!empty($orderId))
        {
            // get the list of sapOrders for the provided orderId
            $sapOrders = $this->_sapOrderCollectionFactory->create();
            $sapOrders->addFieldToFilter('order_id', $orderId);

            foreach($sapOrders as $sapOrder)
            {
                if (!empty($sapOrder))
                {
                    $order = $sapOrder;
                    break;
                }
            }
        }

        return $order;
    }

    /**
     * Return the SAP order from the increment Id
     *
     * @param $incrementId
     * @return \SMG\Sap\Model\SapOrder
     */
    public function getSapOrderByIncrementId($incrementId)
    {
        /**
         * @var \Magento\Sales\Model\Order
         */
        $order = $this->getOrderByIncrementId($incrementId);

        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderFactory->create();
        $this->load($sapOrder, $order->getId(), 'order_id');

        return $sapOrder;
    }
}