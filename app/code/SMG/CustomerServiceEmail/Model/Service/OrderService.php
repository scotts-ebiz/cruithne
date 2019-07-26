<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Service;

use SMG\CustomerServiceEmail\Api\OrderManagementInterface;
use SMG\CustomerServiceEmail\Model\OrderCancelNotifier;
use SMG\CustomerServiceEmail\Model\FailedTransactionNotifier;
use SMG\CustomerServiceEmail\Model\ShipmentNotifier;
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
     * @var ShipmentNotifier
     */
    private $shipmentNotifier;

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
     * @param ShipmentNotifier $shipmentNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct(
        OrderCancelNotifier $notifier,
        FailedTransactionNotifier $failedTransactionNotifier,
        ShipmentNotifier $shipmentNotifier,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteria
    ) {
        $this->notifier = $notifier;
        $this->failedTransactionNotifier = $failedTransactionNotifier;
        $this->shipmentNotifier = $shipmentNotifier;
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
     * Cancel emails a user a specified orders.
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function notifyCancellations(ItemInterface $item)
    {
        $ids = $item->getOrderIds();

        if (!empty($ids)) {
            $orders = $this->orderRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilter('entity_id', $ids, 'in')
                    ->create()
            )->getItems();

            return $this->notifier->notifyOrders($orders);
        }

        return false;
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

            return $this->failedTransactionNotifier->notifyOrders($orders);
        }

        return false;
    }

    /**
     * Shipment email a specified order.
     *
     * @param int $id The order ID.
     * @return bool
     * @throws \Exception
     */
    public function notifyShipmentServiceTeam($id)
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($id);

        if ($order->hasShipments()) {
            $shipments = $order->getShipmentsCollection()->getItems();

            return $this->shipmentNotifier->notify($shipments);
        }

        return false;
    }

    /**
     * Shipment emails a specified orders.
     *
     * @param ItemInterface $item
     * @return bool
     * @throws \Exception
     */
    public function notifyShipmentOrdersServiceTeam(ItemInterface $item)
    {
        $ids = $item->getOrderIds();

        if (!empty($ids)) {
            $orders = $this->orderRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilter('entity_id', $ids, 'in')
                    ->create()
            )->getItems();

            $shipmentItems = [];

            /** @var Order $order */
            foreach ($orders as $order) {
                if ($order->hasShipments()) {
                    $shipments = $order->getShipmentsCollection()->getItems();

                    foreach ($shipments as $shipment) {
                        $shipmentItems[] = $shipment;
                    }
                }
            }

            return $this->shipmentNotifier->notify($shipmentItems);
        }

        return false;
    }
}
