<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 2:37 PM
 */

namespace SMG\Sap\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;

class SapOrderBatch extends AbstractModel
{
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    public function __construct(Context $context,
        \Magento\Framework\Registry $registry,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        SapOrderFactory $sapOrderFactory,
        SapOrderResource $sapOrderResource,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderResource = $sapOrderResource;
    }

    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrderBatch::class);
    }

    /**
     * Get the Sales Order associated with this order id
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $this->getOrderId());

        return $order;
    }

    public function getOrderSap()
    {
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderFactory->create();
        $this->_sapOrderResource->load($sapOrder, $this->getOrderId());

        return $sapOrder;
    }
}