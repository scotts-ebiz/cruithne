<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Order;

use SMG\CustomerServiceEmail\Api\Data\ItemInterface;
use Magento\Framework\DataObject;

/**
 * Class Item
 * @package SMG\CustomerServiceEmail\Model\Order
 */
class Item extends DataObject implements ItemInterface
{
    /**
     * @param mixed $orderIds
     * @return $this
     */
    public function setOrderIds($orderIds)
    {
        return $this->setData(self::ORDER_IDS, $orderIds);
    }

    /**
     * @return mixed
     */
    public function getOrderIds()
    {
        return $this->getData(self::ORDER_IDS);
    }
}
