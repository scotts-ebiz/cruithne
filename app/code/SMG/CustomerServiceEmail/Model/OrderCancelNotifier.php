<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model;

use SMG\CustomerServiceEmail\Model\Order\Email\Sender\OrderCancelSender;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class OrderCancelNotifier
 * @package SMG\CustomerServiceEmail\Model
 */
class OrderCancelNotifier
{
    /**
     * @var OrderCancelSender
     */
    private $sender;

    /**
     * @param OrderCancelSender $sender
     */
    public function __construct(OrderCancelSender $sender)
    {
        $this->sender = $sender;
    }

    /**
     * Notify user
     *
     * @param AbstractModel $model
     * @return bool
     */
    public function notify(AbstractModel $model)
    {
        return $this->sender->send($model);
    }

    /**
     * Notify service team multiple orders
     *
     * @param OrderInterface[] $orders
     * @return bool
     * @throws /Exception
     */
    public function notifyOrders(array $orders)
    {
        return $this->sender->sendOrders($orders);
    }
}
