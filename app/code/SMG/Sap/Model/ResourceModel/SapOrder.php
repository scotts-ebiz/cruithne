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

    protected function _construct()
    {
        $this->_init('sales_order_sap', 'entity_id');
    }

    public function __construct(Context $context,
        OrderFactory $orderFactory,
        Order $orderResource,
        $connectionName = null)
    {
        parent::__construct($context, $connectionName);

        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
    }

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
}