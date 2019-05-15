<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 5/10/19
 * Time: 1:16 PM
 */

namespace SMG\CreditReason\Plugin\Model\Order;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;

use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchItemFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchItem as SapOrderBatchItemResource;

class CreditmemoRepository
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
     * @var SapOrderBatchItemFactory
     */
    protected $_sapOrderBatchItemFactory;

    /**
     * @var SapOrderBatchItemResource
     */
    protected $_sapOrderBatchItemResource;

    /**
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var ItemResource
     */
    protected $_itemResource;

    /**
     * CreditmemoRepository constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SapOrderBatchItemFactory $sapOrderBatchItemFactory
     * @param SapOrderBatchItemResource $sapOrderBatchItemResource
     * @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     */
    public function __construct(LoggerInterface $logger,
        RequestInterface $request,
        SapOrderBatchItemFactory $sapOrderBatchItemFactory,
        SapOrderBatchItemResource $sapOrderBatchItemResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource)
    {
        $this->_logger = $logger;
        $this->_request = $request;
        $this->_sapOrderBatchItemFactory = $sapOrderBatchItemFactory;
        $this->_sapOrderBatchItemResource = $sapOrderBatchItemResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
    }

    public function beforeSave(\Magento\Sales\Model\Order\CreditmemoRepository $subject, \Magento\Sales\Api\Data\CreditmemoInterface $entity)
    {
        try
        {
            // get the parameters from the page
            $params = $this->_request->getParams();

            // get the credit memo
            $creditMemoParams = $params['creditmemo'];

            // get the items from the form
            $itemsParams = $creditMemoParams['items'];

            // get the items on the credit memo
            $items = $entity->getItems();

            // create the order item object to be used later
            /**
             * @var \Magento\Sales\Model\Order\Item $orderItem
             */
            $orderItem = $this->_itemFactory->create();

            // loop through the items on the credit memo
            /**
             * @var \Magento\Sales\Api\Data\CreditmemoInterface[] $items
             */
            foreach ($items as $item)
            {
                // get the order item id
                $orderItemId = $item->getData("order_item_id");

                // load the order item from the order item id
                $this->_itemResource->load($orderItem, $orderItemId);

                // determine if this is a bundle product
                // if it is then we will wait to update the reason code values
                // otherwise update the reason code values now
                $productType = $orderItem->getProductType();
                if (isset($productType) && $productType != 'bundle')
                {
                    // get the refunded reason code
                    $refundedReadonCode = $itemsParams[$orderItemId]['refunded_reason_code'];
                    if (isset($refundedReadonCode))
                    {
                        // set the refunded reason code on the credit memo item
                        $item->setData('refunded_reason_code', $refundedReadonCode);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        // return the parameters for the actual save run
        return [$entity];
    }

    public function afterSave(\Magento\Sales\Model\Order\CreditmemoRepository $subject, $result)
    {
        try
        {
            // get order id
            $orderId = $result->getData("order_id");

            // get the credit memo order id
            $creditMemoOrderId = $result->getId();

            // get the credit memo items for processing
            $items = $result->getItems();

            // create the order item object to be used later
            /**
             * @var \Magento\Sales\Model\Order\Item $orderItem
             */
            $orderItem = $this->_itemFactory->create();

            // loop through the items
            foreach ($items as $item)
            {
                // get the order item id
                $orderItemId = $item->getData("order_item_id");

                // load the order item from the order item id
                $this->_itemResource->load($orderItem, $orderItemId);

                // determine if this is a bundle product
                // if it is then we will wait to update the reason code values
                // otherwise update the reason code values now
                $productType = $orderItem->getProductType();
                if (isset($productType) && $productType != 'bundle')
                {
                    // create a record in the sales order sap batch items table
                    $sapOrderBatchItem = $this->_sapOrderBatchItemFactory->create();

                    $sapOrderBatchItem->setData('creditmemo_order_id', $creditMemoOrderId);
                    $sapOrderBatchItem->setData('order_id', $orderId);
                    $sapOrderBatchItem->setData('order_item_id', $item->getData("order_item_id"));
                    $sapOrderBatchItem->setData('sku', $item->getData('sku'));
                    $sapOrderBatchItem->setData('is_credit', true);

                    // save to the database
                    $this->_sapOrderBatchItemResource->save($sapOrderBatchItem);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        // return
        return $result;
    }
}