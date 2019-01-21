<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Api\Data;

/**
 * Interface ItemInterface
 * @package SMG\CustomerServiceEmail\Api\Data
 */
interface ItemInterface
{
    /**
     * Ids
     */
    const ORDER_IDS = 'order_ids';

    /**
     * @param mixed $orderIds
     * @return $this
     */
    public function setOrderIds($orderIds);

    /**
     * @return mixed
     */
    public function getOrderIds();
}
