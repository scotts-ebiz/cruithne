<?php

namespace SMG\BackendService\Model;

use SMG\BackendService\Api\OrderbyidInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Message\ManagerInterface;

class Orderbyid implements OrderbyidInterface
{

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Orderbyid constructor.
     * @param OrderFactory $orderFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        OrderFactory $orderFactory,
        ManagerInterface $messageManager
    ) {
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderById($orderIncrementid, $email)
    {
        $data = [];
        if (!empty($orderIncrementid) && !empty($email)) {
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementid);
            if ($order && $order->getCustomerEmail() == $email) {

                $data["result"] = $order->getData();

                foreach ($order->getAllItems() as $item) {
                    $data["result"]['items'][$item->getItemId()] = $item->getData();
                }

                $tracksCollection = $order->getTracksCollection();
                $trackNumbers = [];
                foreach ($tracksCollection->getItems() as $track) {
                    $trackNumbers[$track->getTitle()] = $track->getTrackNumber();
                }
                $data["result"]['tracking'] = $trackNumbers;
            }
        }

        return $data;
    }

}