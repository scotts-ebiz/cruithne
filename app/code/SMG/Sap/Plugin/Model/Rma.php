<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Sap\Plugin\Model;
use Magento\Framework\App\RequestInterface;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchRmaFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchRma as SapOrderBatchRmaResource;
class Rma
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
     * @var SapOrderBatchRmaFactory
     */
    protected $_sapOrderBatchRmaFactory;
    /**
     * @var SapOrderBatchRmaResource
     */
    protected $_sapOrderBatchRmaResource;
    /**
     * @var ItemFactory
     */
    protected $_itemFactory;
    /**
     * @var ItemResource
     */
    protected $_itemResource;

    /**
     * @var DateTimeFactory
     */
    protected $_dateFactory;

    protected $_currentReasonCode;

    /**
     * RmaRepository constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SapOrderBatchRmaFactory $sapOrderBatchRmaFactory
     * @param SapOrderBatchRmaResource $sapOrderBatchRmaResource
     * @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     */
    public function __construct(LoggerInterface $logger,
                                RequestInterface $request,
                                SapOrderBatchRmaFactory $sapOrderBatchRmaFactory,
                                SapOrderBatchRmaResource $sapOrderBatchRmaResource,
                                ItemFactory $itemFactory,
                                ItemResource $itemResource,
                                DateTimeFactory $dateFactory)
    {
        $this->_logger = $logger;
        $this->_request = $request;
        $this->_sapOrderBatchRmaFactory = $sapOrderBatchRmaFactory;
        $this->_sapOrderBatchRmaResource = $sapOrderBatchRmaResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
        $this->_dateFactory = $dateFactory;
    }

    public function beforeSave(\Magento\Rma\Model\Rma $subject)
    {
        $items = $subject->getItems();
        //$this->_logger->error(serialize($items));
        if (!empty($items)) {
            foreach ($items as $item) {
                $qty = $item->getQtyRequested();
                $reason = $item->getReason();
                $this->_currentReasonCode = $reason;
                $item->setReason(null);

                if (!empty($qty) && $qty > 0) {
                    $item-> setQtyApproved($qty);
                    $item->setQtyAuthorized($qty);
                    $item->setQtyReturned($qty);
                    $item->setStatus(Status::STATE_APPROVED);
                }
            }
            $subject->setStatus(Status::STATE_PROCESSED_CLOSED);
        }
    }


    public function afterSave(\Magento\Rma\Model\Rma $subject, $result)
    {
        try {
            // get order id
            $orderId = $result->getData("order_id");
            // get the rma id
            $rmaId = $result->getId();
            // get the rma items for processing
            $items = $result->getItems();

            // create the order item object to be used later
            /**
             * @var \Magento\Sales\Model\Order\Item $orderItem
             */
            $orderItem = $this->_itemFactory->create();
            // loop through the items
            foreach ($items as $item) {
                // get the order item id
                $orderItemId = $item->getData("order_item_id");
                // load the order item from the order item id
                $this->_itemResource->load($orderItem, $orderItemId);
                // determine if this is a bundle product
                // if it is then we will wait to update the reason code values
                // otherwise update the reason code values now
                $productType = $orderItem->getProductType();
                if (isset($productType) && $productType != 'bundle') {
                    // create a record in the sales order sap batch items table
                    $sapOrderBatchRma = $this->_sapOrderBatchRmaFactory->create();
                    $sapOrderBatchRma->setData('rma_id', $rmaId);
                    $sapOrderBatchRma->setData('order_id', $orderId);
                    $sapOrderBatchRma->setData('order_item_id', $item->getData("order_item_id"));
                    $sapOrderBatchRma->setData('sku', $item->getData('product_sku'));
                    $sapOrderBatchRma->setData('is_return', true);
                    $sapOrderBatchRma->setData('reason_id', $this->_currentReasonCode);
                    // save to the database
                    $this->_sapOrderBatchRmaResource->save($sapOrderBatchRma);
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }
        // return
        return $result;
    }
}