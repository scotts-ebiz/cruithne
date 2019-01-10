<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model;

use SMG\CustomerServiceEmail\Model\Order\Email\Sender\OrderFailedSender;
use Magento\Sales\Model\AbstractModel;

/**
 * Class FailedTransactionNotifier
 * @package SMG\CustomerServiceEmail\Model
 */
class FailedTransactionNotifier
{
    /**
     * @var OrderFailedSender
     */
    private $sender;

    /**
     * @param OrderFailedSender $sender
     */
    public function __construct(OrderFailedSender $sender)
    {
        $this->sender = $sender;
    }

    /**
     * Notify service team
     *
     * @param AbstractModel $model
     * @return bool
     */
    public function notify(AbstractModel $model)
    {
        return $this->sender->send($model);
    }
}
