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
use SMG\Sap\Model\SapOrderItemFactory;
use SMG\Sap\Model\SapOrderStatusFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem;
use SMG\Sap\Model\ResourceModel\SapOrderStatus;

class SapOrderItemHistory extends AbstractDb
{
    /**
     * @var SapOrderItemFactory
     */
    protected $_sapOrderItemFactory;

    /**
     * @var SapOrderItem
     */
    protected $_sapOrderItem;

    /**
     * @var SapOrderStatusFactory
     */
    protected $_sapOrderStatusFactory;

    /**
     * @var SapOrderStatus
     */
    protected $_sapOrderStatus;

    protected function _construct()
    {
        $this->_init('sales_order_sap_item_history', 'entity_id');
    }

    public function __construct(Context $context,
        SapOrderItemFactory $sapOrderItemFactory,
        SapOrderItem $sapOrderItem,
        SapOrderStatusFactory $sapOrderStatusFactory,
        SapOrderStatus $sapOrderStatus,
        $connectionName = null)
    {
        parent::__construct($context, $connectionName);

        $this->_sapOrderItemFactory = $sapOrderItemFactory;
        $this->_sapOrderItem = $sapOrderItem;
        $this->_sapOrderStatusFactory = $sapOrderStatusFactory;
        $this->_sapOrderStatus = $sapOrderStatus;
    }

    public function getSapOrderItem($orderSapItemId)
    {
        /**
         * @var \SMG\Sap\Model\SapOrderItem
         */
        $sapOrderItem = $this->_sapOrderItemFactory->create();

        // load the data for the order id
        $this->_sapOrderItem->load($sapOrderItem, $orderSapItemId);

        return $sapOrderItem;
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
}