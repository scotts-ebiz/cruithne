<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Service;

use SMG\CustomerServiceEmail\Api\OrderManagementInterface;
use SMG\CustomerServiceEmail\Model\OrderCancelNotifier;
use SMG\CustomerServiceEmail\Model\FailedTransactionNotifier;
use SMG\CustomerServiceEmail\Api\Data\ItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;

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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param OrderCancelNotifier $notifier
     * @param FailedTransactionNotifier $failedTransactionNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct(
        OrderCancelNotifier $notifier,
        FailedTransactionNotifier $failedTransactionNotifier,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteria
    ) {
        $this->notifier = $notifier;
        $this->failedTransactionNotifier = $failedTransactionNotifier;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteria;
    }

    /**
     * Cancel emails a user a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notify($id)
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($id);
        return $this->notifier->notify($order);
    }

    /**
     * Failed capture transaction email a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     */
    public function notifyServiceTeam($id)
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($id);
        return $this->failedTransactionNotifier->notify($order);
    }

    /**
     * Failed capture transaction emails a specified orders.
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function notifyEmailsServiceTeam(ItemInterface $item)
    {
        $ids = $item->getOrderIds();

        if (!empty($ids)) {
            $orders = $this->orderRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilter('entity_id', $ids, 'in')
                    ->create()
            )->getItems();

            /** @var Order $order */
            foreach ($orders as $order) {
                $this->failedTransactionNotifier->notify($order);
            }

            return true;
        }

        return false;
    }
}
