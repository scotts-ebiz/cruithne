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

class SapOrderBatchItem extends AbstractModel
{
    /**
     * @var \SMG\Sap\Model\ResourceModel\SapOrderBatchItem
     */
    protected $_resourceModel;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    public function __construct(Context $context,
        \Magento\Framework\Registry $registry,
        \SMG\Sap\Model\ResourceModel\SapOrderBatch $resourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_resourceModel = $resourceModel;
    }

    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrderBatchItem::class);
    }

    public function getOrder()
    {
        if (!$this->_order)
        {
            $this->_order = $this->_resourceModel->getOrder($this->getOrderId());
        }

        return $this->_order;
    }
}