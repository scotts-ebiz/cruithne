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
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchCreditmemoFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchCreditmemo as SapOrderBatchCreditmemoResource;

class CreditmemoRepository
{
    public const ORDER_ERROR_REASON_CODE = '040';

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var SapOrderBatchCreditmemoFactory
     */
    protected $_sapOrderBatchCreditmemoFactory;

    /**
     * @var SapOrderBatchCreditmemoResource
     */
    protected $_sapOrderBatchCreditmemoResource;

    /**
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var ItemResource
     */
    protected $_itemResource;

    /**
     * @var \SMG\CreditReason\Plugin\Model\Order\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * CreditmemoRepository constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SapOrderBatchCreditmemoFactory $sapOrderBatchCreditmemoFactory
     * @param SapOrderBatchCreditmemoResource $sapOrderBatchCreditmemoResource
     * @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     */
    public function __construct(LoggerInterface $logger,
        RequestInterface $request,
        SapOrderBatchCreditmemoFactory $sapOrderBatchCreditmemoFactory,
        SapOrderBatchCreditmemoResource $sapOrderBatchCreditmemoResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        OrderFactory $orderFactory,
        OrderResource $orderResource)
    {
        $this->_logger = $logger;
        $this->_request = $request;
        $this->_sapOrderBatchCreditmemoFactory = $sapOrderBatchCreditmemoFactory;
        $this->_sapOrderBatchCreditmemoResource = $sapOrderBatchCreditmemoResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
    }

    public function beforeSave(\Magento\Sales\Model\Order\CreditmemoRepository $subject, \Magento\Sales\Api\Data\CreditmemoInterface $entity)
    {
        try
        {
            // Get the order for this credit memo.
            $order = $this->_orderFactory->create();
            $this->_orderResource->load($order, $entity->getOrderId());

            // Check if this is a lawn subscription order.
            if ($order->getData('subscription_id')) {
                // This is a subscription order that has been cancelled.
                $items = $entity->getItems();
                $orderItem = $this->_itemFactory->create();

                foreach ($items as $item) {
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
                        // set the refunded reason code on the credit memo item
                        $item->setData('refunded_reason_code', self::ORDER_ERROR_REASON_CODE);
                    }
                }

                return [$entity];
            }

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

            // get the keys for the items on the form
            // the keys are the orderItemId values that
            // we need to check product information
            // loop through the keys as these are the items
            // that we want to update
            $keys = array_keys($itemsParams);
            foreach ($keys as $key)
            {
                // loop through the items on the credit memo
                /**
                 * @var \Magento\Sales\Api\Data\CreditmemoInterface[] $items
                 */
                foreach ($items as $item)
                {
                    // get the order item id
                    $orderItemId = $item->getData("order_item_id");

                     // get the order qty
                     $orderQty = $item->getData("qty");

                    // determine if this was the item that was modified
                    // on the creditmemo form
                    if ($orderItemId == $key)
                    {
                        // load the order item from the order item id
                        $this->_itemResource->load($orderItem, $orderItemId);

                        // determine if this is a bundle product
                        // if it is then we will wait to update the reason code values
                        // otherwise update the reason code values now
                        $productType = $orderItem->getProductType();
                        if (isset($productType) && $productType != 'bundle' && $orderQty > 0)
                        {
                            // get the refunded reason code
                            $refundedReadonCode = $itemsParams[$orderItemId]['refunded_reason_code'];
                            if (isset($refundedReadonCode))
                            {
                                // set the refunded reason code on the credit memo item
                                $item->setData('refunded_reason_code', $refundedReadonCode);
                            }
                        }

                        // return out of the loop
                        break;
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

                // get the order qty
                $orderQty = $item->getData("qty");


                // load the order item from the order item id
                $this->_itemResource->load($orderItem, $orderItemId);

                // determine if this is a bundle product
                // if it is then we will wait to update the reason code values
                // otherwise update the reason code values now
                $productType = $orderItem->getProductType();
                if (isset($productType) && $productType != 'bundle' && $orderQty > 0)
                {
                    // create a record in the sales order sap batch items table
                    $sapOrderBatchCreditmemo = $this->_sapOrderBatchCreditmemoFactory->create();

                    $sapOrderBatchCreditmemo->setData('creditmemo_order_id', $creditMemoOrderId);
                    $sapOrderBatchCreditmemo->setData('order_id', $orderId);
                    $sapOrderBatchCreditmemo->setData('order_item_id', $item->getData("order_item_id"));
                    $sapOrderBatchCreditmemo->setData('sku', $item->getData('sku'));
                    $sapOrderBatchCreditmemo->setData('is_credit', true);

                    // save to the database
                    $this->_sapOrderBatchCreditmemoResource->save($sapOrderBatchCreditmemo);
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
