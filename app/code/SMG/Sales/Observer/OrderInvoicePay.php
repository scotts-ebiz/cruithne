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

class OrderInvoicePay implements ObserverInterface
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
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        /**
         * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
         */
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $this->_sapOrderBatchResource->load($sapOrderBatch, $order->getId(), 'order_id');

        // set the values
        $sapOrderBatch->setData('order_id', $order->getId());
        $sapOrderBatch->setData('is_order', true);

        // save the record
        $this->_sapOrderBatchResource->save($sapOrderBatch);
    }
}