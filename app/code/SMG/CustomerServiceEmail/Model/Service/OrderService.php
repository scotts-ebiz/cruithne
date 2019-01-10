<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Service;

use SMG\CustomerServiceEmail\Api\OrderManagementInterface;
use SMG\CustomerServiceEmail\Model\OrderCancelNotifier;
use SMG\CustomerServiceEmail\Model\FailedTransactionNotifier;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderService
 * @package SMG\CustomerServiceEmail\Model\Service
 */
class OrderService implements OrderManagementInterface
{
    /**
     * @var OrderCancelNotifier
     */
    private $notifier;

    /**
     * @var FailedTransactionNotifier
     */
    private $failedTransactionNotifier;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param OrderCancelNotifier $notifier
     * @param FailedTransactionNotifier $failedTransactionNotifier
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderCancelNotifier $notifier,
        FailedTransactionNotifier $failedTransactionNotifier,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->notifier = $notifier;
        $this->failedTransactionNotifier = $failedTransactionNotifier;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Cancel emails a user a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notify($id)
    {
        $order = $this->orderRepository->get($id);
        return $this->notifier->notify($order);
    }

    /**
     * Failed capture transaction emails a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notifyServiceTeam($id)
    {
        $order = $this->orderRepository->get($id);
        return $this->failedTransactionNotifier->notify($order);
    }
}
