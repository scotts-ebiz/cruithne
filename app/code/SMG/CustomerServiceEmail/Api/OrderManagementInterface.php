<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Api;

/**
 * Interface OrderManagementInterface
 * @package SMG\CustomerServiceEmail\Api
 * @api
 */
interface OrderManagementInterface
{
    /**
     * Cancellation emails a user a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notify($id);

    /**
     * Cancellation emails a user a specified orders.
     *
     * @param \SMG\CustomerServiceEmail\Api\Data\ItemInterface $item
     * @return bool
     */
    public function notifyCancellations(\SMG\CustomerServiceEmail\Api\Data\ItemInterface $item);

    /**
     * Failed capture transaction email a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notifyServiceTeam($id);

    /**
     * Failed capture transaction emails a specified orders.
     *
     * @param \SMG\CustomerServiceEmail\Api\Data\ItemInterface $item
     * @return bool
     */
    public function notifyEmailsServiceTeam(\SMG\CustomerServiceEmail\Api\Data\ItemInterface $item);

    /**
     * Shipment email a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notifyShipmentServiceTeam($id);

    /**
     * Shipment email a specified orders.
     *
     * @param \SMG\CustomerServiceEmail\Api\Data\ItemInterface $item
     * @return bool
     */
    public function notifyShipmentOrdersServiceTeam(\SMG\CustomerServiceEmail\Api\Data\ItemInterface $item);
}
