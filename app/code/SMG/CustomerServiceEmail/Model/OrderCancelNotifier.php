<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model;

use SMG\CustomerServiceEmail\Model\Order\Email\Sender\OrderCancelSender;
use Magento\Sales\Model\AbstractModel;

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
}
