<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model;

use SMG\CustomerServiceEmail\Model\Order\Email\Sender\ShipmentSender;

/**
 * Class ShipmentNotifier
 * @package SMG\CustomerServiceEmail\Model
 */
class ShipmentNotifier
{
    /**
     * @var ShipmentSender
     */
    private $sender;

    /**
     * @param ShipmentSender $sender
     */
    public function __construct(ShipmentSender $sender)
    {
        $this->sender = $sender;
    }

    /**
     * Notify service team
     *
     * @param array $shipments
     * @return bool
     * @throws \Exception
     */
    public function notify(array $shipments)
    {
        return $this->sender->send($shipments);
    }
}
