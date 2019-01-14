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

class SapOrderHistory extends AbstractModel
{
    /**
     * @var \SMG\Sap\Model\ResourceModel\SapOrderHistory
     */
    protected $_resourceModel;

    /**
     * @var \SMG\Sap\Model\SapOrder
     */
    protected $_sapOrder;

    /**
     * @var \SMG\Sap\Model\SapOrderStatus
     */
    protected $_sapOrderStatus;

    public function __construct(Context $context,
        \Magento\Framework\Registry $registry,
        \SMG\Sap\Model\ResourceModel\SapOrderHistory $resourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_resourceModel = $resourceModel;
    }

    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrderHistory::class);
    }

    public function getSapOrder()
    {
        if (!$this->_sapOrder)
        {
            $this->_sapOrder = $this->_resourceModel->getSapOrder($this->getOrderSapId());
        }

        return $this->_sapOrder;
    }

    public function getStatus()
    {
        if (!$this->_sapOrderStatus)
        {
            $this->_sapOrderStatus = $this->_resourceModel->getStatus($this->getStatus());
        }
    }
}