<?php

namespace SMG\CreditReason\Observer\Backend\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchItemFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchItem as SapOrderBatchItemResource;

class OrderCreditMemoSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SapOrderBatchItemFactory
     */
    protected $_sapOrderBatchItemFactory;

    /**
     * @var SapOrderBatchItemResource
     */
    protected $_sapOrderBatchItemResource;

    /**
     * OrderCreditMemoSaveAfter constructor.
     *
     * @param LoggerInterface $logger
     * @param SapOrderBatchItemFactory $sapOrderBatchItemFactory
     * @param SapOrderBatchItemResource $sapOrderBatchItemResource
     */
    public function __construct(LoggerInterface $logger,
        SapOrderBatchItemFactory $sapOrderBatchItemFactory,
        SapOrderBatchItemResource $sapOrderBatchItemResource)
    {
        $this->_logger = $logger;
        $this->_sapOrderBatchItemFactory = $sapOrderBatchItemFactory;
        $this->_sapOrderBatchItemResource = $sapOrderBatchItemResource;
    }

    public function execute(Observer $observer)
    {
        try
        {
            /**
             * @var \Magento\Sales\Model\Order\Creditmemo $creditMemo
             */
            $creditMemo = $observer->getData('creditmemo');

            // Get the Credit Memo Items
            // loop through the items and update the
            $creditMemoItems = $creditMemo->getItems();
            foreach ($creditMemoItems as $creditMemoItem)
            {
                // create a record in the sales order sap batch items table
                $sapOrderBatchItem = $this->_sapOrderBatchItemFactory->create();

                $sapOrderBatchItem->setData('creditmemo_order_id', $creditMemo->getId());
                $sapOrderBatchItem->setData('order_id', $creditMemo->getOrderId());
                $sapOrderBatchItem->setData('order_item_id', $creditMemoItem->getOrderItemId());
                $sapOrderBatchItem->setData('sku', $creditMemoItem->getSku());
                $sapOrderBatchItem->setData('is_credit', true);

                // save to the database
                $this->_sapOrderBatchItemResource->save($sapOrderBatchItem);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }
    }
}