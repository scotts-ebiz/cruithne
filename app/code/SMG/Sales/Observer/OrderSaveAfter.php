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
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;

class OrderSaveAfter implements ObserverInterface
{
    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    public function __construct(SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource)
    {
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
    }

    public function execute(Observer $observer)
    {
        // get the order
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getData('order');

        /**
         * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
         */
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $this->_sapOrderBatchResource->load($sapOrderBatch, $order->getId(), 'order_id');

        // get the is order flag to see if it has already been set
        $isConsumerData = $sapOrderBatch->getData('is_consumer_data');
        if (!isset($isConsumerData))
        {
            // set the values
            $sapOrderBatch->setData('order_id', $order->getId());
            $sapOrderBatch->setData('is_order', false);
            $sapOrderBatch->setData('is_consumer_data', true);

            // save the record
            $this->_sapOrderBatchResource->save($sapOrderBatch);
        }
    }
}