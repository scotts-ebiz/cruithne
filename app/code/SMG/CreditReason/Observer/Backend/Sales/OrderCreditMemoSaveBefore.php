<?php

namespace SMG\CreditReason\Observer\Backend\Sales;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item as ItemResource;
use Psr\Log\LoggerInterface;

class OrderCreditMemoSaveBefore implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var ItemFactory
     */
    protected $_creditMemoItemFactory;

    /**
     * @var ItemResource
     */
    protected $_creditMemoItemResource;

    /**
     * OrderCreditMemoSaveAfter constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ItemFactory $creditMemoItemFactory
     * @param ItemResource $creditMemoItemResource
     */
    public function __construct(LoggerInterface $logger,
        RequestInterface $request,
        ItemFactory $creditMemoItemFactory,
        ItemResource $creditMemoItemResource)
    {
        $this->_logger = $logger;
        $this->_request = $request;
        $this->_creditMemoItemFactory = $creditMemoItemFactory;
        $this->_creditMemoItemResource = $creditMemoItemResource;
    }

    public function execute(Observer $observer)
    {
        // get the parameters from the page
        $params = $this->_request->getParams();

        // get the credit memo
        $creditMemoParam = $params['creditmemo'];

        // get the items from the form
        $itemsParam = $creditMemoParam['items'];

        /**
         * @var \Magento\Sales\Model\Order\Creditmemo $creditMemo
         */
        $creditMemo = $observer->getData('creditmemo');

        // Get the Credit Memo Items
        // loop through the items and update the
        $creditMemoItems = $creditMemo->getItems();
        foreach ($creditMemoItems as $creditMemoItem)
        {
            // get the order item id
            $orderItemId = $creditMemoItem->getOrderItemId();

            // determine if the item was on the form
            if (array_key_exists($orderItemId, $itemsParam))
            {
                $creditMemoItem->setData('refunded_reason_code', $itemsParam[$orderItemId]['refunded_reason_code']);
            }
        }
    }
}