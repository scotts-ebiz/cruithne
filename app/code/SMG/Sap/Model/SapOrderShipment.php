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
use SMG\Sap\Model\ResourceModel\SapOrderShipment as SapOrderShipmentResource;

class SapOrderShipment extends AbstractModel
{
    /**
     * @var SapOrderShipmentResource
     */
    protected $_resourceModel;

    public function __construct(Context $context,
        \Magento\Framework\Registry $registry,
        SapOrderShipmentResource $resourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_resourceModel = $resourceModel;
    }

    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrderShipment::class);
    }

    public function getSapOrderItems($orderId)
    {
        return $this->_resourceModel->getSapOrderItems($orderId);
    }

    public function getSapOrderItem($orderId, $orderSapItemId)
    {
        return $this->_resourceModel->getSapOrderItem($orderId, $orderSapItemId);
    }
}