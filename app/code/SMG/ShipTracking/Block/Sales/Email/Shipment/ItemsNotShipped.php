<?php
/**
 * @copyright Copyright (c) 2020 SMG, LLC
 */

namespace SMG\ShipTracking\Block\Sales\Email\Shipment;

use Magento\Framework\View\Element\Template;
use SMG\ShipTracking\Model\ConfigProvider;
use SMG\Sap\Model\ResourceModel\SapOrder;

/**
 * Class ItemsNotShipped
 * @package SMG\ShipTracking\Block\Sales\Email\Shipment
 */
class ItemsNotShipped extends Template
{
    const PARTIAL_ORDER_STATUS = 'order_partially_shipped';
    /**
     * @var SapOrder
     */
    protected $_sapOrderResource;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param Template\Context $context
     * @param ConfigProvider $configProvider
     * @param array $data
     * @param SapOrder $sapOrderResource
     */
    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        array $data = [],
        SapOrder $sapOrderResource
    ) {
        parent::__construct($context, $data);

        $this->configProvider = $configProvider;
        $this->_sapOrderResource = $sapOrderResource;
    }

    public function getItemsNotShipped($orderId)
    {
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);

        // If we do not have a partially shipped order status, then return an empty array.
        if (!$sapOrder || $sapOrder->getOrderStatus() !== self::PARTIAL_ORDER_STATUS) {
            return [];
        }

        // Grab all items that have a partially shipped status.
        $sapOrderItems = $sapOrder->getSapOrderItems();
        $sapOrderItems->addFieldToFilter('order_status', ['eq' => self::PARTIAL_ORDER_STATUS]);

        return $sapOrderItems;
    }
}
