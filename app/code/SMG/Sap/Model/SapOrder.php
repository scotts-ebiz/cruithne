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
use Magento\Sales\Model\Order;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;

class SapOrder extends AbstractModel
{
    /**
     * @var SapOrderResource
     */
    protected $_resourceModel;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        SapOrderResource $resourceModel,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_resourceModel = $resourceModel;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
    }

    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrder::class);
    }

    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->_resourceModel->getOrder($this->getOrderId());
        }

        return $this->_order;
    }

    public function getOrderByIncrementId($incrementId)
    {
        return $this->_resourceModel->getOrderByIncrementId($incrementId);
    }

    public function getSapOrderByOrderId($orderId)
    {
        return $this->_resourceModel->getSapOrderByOrderId($orderId);
    }

    public function getSapOrderByIncrementId($incrementId)
    {
        return $this->_resourceModel->getSapOrderByIncrementId($incrementId);
    }

    /**
     * Get the Sap Order Item for this Sap Order
     *
     * @return \SMG\Sap\Model\ResourceModel\SapOrderItem\Collection
     */
    public function getSapOrderItems()
    {
        // create the collection that will be returned
        $sapOrderBatches = $this->_sapOrderItemCollectionFactory->create();

        // add filter for this sap order
        $sapOrderBatches->addFieldToFilter('order_sap_id', ['eq' => $this->getId()]);

        // return
        return $sapOrderBatches;
    }

    /**
     * Get a list of unique tracking numbers for this Sap Order
     *
     * @return \SMG\Sap\Model\ResourceModel\SapOrderItem\Collection
     */
    public function getSapOrderTrackingNumbers()
    {
        $sapOrderItems = $this->getSapOrderItems();

        // Grab all the unique tracking numbers.
        $trackingNumbers = [];

        /**
         * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
         */
        foreach ($sapOrderItems as $sapOrderItem) {
            $shipments =  $sapOrderItem->getSapOrderShipments($sapOrderItem->getId());

            /**
            * @var \SMG\Sap\Model\SapOrderShipment $shipment
            */
            foreach ($shipments as $shipment) {
                $trackingNumber = $shipment->getData('ship_tracking_number');

                if (!in_array($trackingNumber, $trackingNumbers)) {
                    $trackingNumbers[] = $trackingNumber;
                }
            }
        }

        return $trackingNumbers;
    }
}
