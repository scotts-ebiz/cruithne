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

class SapOrderItemHistory extends AbstractModel
{
    /**
     * @var \SMG\Sap\Model\ResourceModel\SapOrderItemHistory
     */
    protected $_resourceModel;

    /**
     * @var \SMG\Sap\Model\SapOrderItem
     */
    protected $_sapOrderItem;

    /**
     * @var \SMG\Sap\Model\SapOrderStatus
     */
    protected $_sapOrderStatus;

    public function __construct(Context $context,
        \Magento\Framework\Registry $registry,
        \SMG\Sap\Model\ResourceModel\SapOrderItemHistory $resourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_resourceModel = $resourceModel;
    }

    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrderItemHistory::class);
    }

    public function getSapOrderItem()
    {
        if (!$this->_sapOrderItem)
        {
            $this->_sapOrderItem = $this->_resourceModel->getSapOrderItem($this->getOrderSapItemId());
        }

        return $this->_sapOrderItem;
    }

    public function getStatus()
    {
        if (!$this->_sapOrderStatus)
        {
            $this->_sapOrderStatus = $this->_resourceModel->getStatus($this->getStatus());
        }
    }
}