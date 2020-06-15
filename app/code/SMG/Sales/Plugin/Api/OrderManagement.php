<?php
namespace SMG\Sales\Plugin\Api;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Class OrderManagement
 */
class OrderManagement
{

     /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * MyCustomClass constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param OrderManagementInterface $subject
     * @param OrderInterface           $order
     *
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPlace(
        OrderManagementInterface $subject,
        OrderInterface $result
    ) {
        $orderId = $result->getId();
        $orderItems = $result->getItems();
        $status = false;
        if ($orderId) {
            foreach ($orderItems as $item){
               $sku = $item->getSku();
               if($sku == 'Payment SKU')
               {
                   $status = true;
                   break;
               }
            }
            if($status)
            {
              $order = $this->orderRepository->get($orderId);
              $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
              $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
              $this->orderRepository->save($order);
                
            }
        }
        return $result;
    }
}