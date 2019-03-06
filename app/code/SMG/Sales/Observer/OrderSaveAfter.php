<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 3/5/19
 * Time: 3:14 PM
 */

namespace SMG\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;

class OrderSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    public function __construct(LoggerInterface $logger,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource)
    {
        $this->_logger = $logger;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
    }

    public function execute(Observer $observer)
    {
        $this->_logger->debug("I am in the observer.");

        // get the order
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getData('order');

        /**
         * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
         */
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();

        // set the values
        $sapOrderBatch->setData('order_id', $order->getId());
        $sapOrderBatch->setData('is_order', true);

        // save the record
        $this->_sapOrderBatchResource->save($sapOrderBatch);
    }
}