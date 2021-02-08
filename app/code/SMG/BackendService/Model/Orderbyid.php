<?php

namespace SMG\BackendService\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use SMG\BackendService\Model\Service\Order;
use Magento\Catalog\Api\ProductRepositoryInterface;
use SMG\BackendService\Api\OrderbyidInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Message\ManagerInterface;

class Orderbyid implements OrderbyidInterface
{

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentItemRepositoryInterface
     */
    protected $shipmentItem;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Orderbyid constructor.
     * @param OrderFactory $orderFactory
     * @param Order $order
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param ShipmentItemRepositoryInterface $shipmentItemRepositoryInterface
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        OrderFactory $orderFactory,
        Order $order,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        ShipmentItemRepositoryInterface $shipmentItem,
        ManagerInterface $messageManager
    )
    {
        $this->orderFactory = $orderFactory;
        $this->order = $order;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentItem = $shipmentItem;
        $this->messageManager = $messageManager;
    }


    /**
     * {@inheritdoc}
     */
    public function getOrderById($orderIncrementid, $email)
    {
        $data = [];

        if (!empty($orderIncrementid) && !empty($email)) {
            $order_data = $this->orderFactory->create()->loadByIncrementId($orderIncrementid);

            if ($order_data && $order_data->getCustomerEmail() == $email) {

                $data["result"] = $this->order->buildOrderObject(
                    $order_data, "", "", ""
                );
            }
        }

        return $data;
    }

}