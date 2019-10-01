<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 8/6/19
 * Time: 3:28 PM
 */

namespace SMG\Sales\Plugin\Model\Order;

use Psr\Log\LoggerInterface;

class Payment
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $subject
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return mixed
     */
    public function beforeRefund(\Magento\Sales\Model\Order\Payment $subject, $creditmemo)
    {
        try
        {
            if (!$creditmemo->getInvoice())
            {
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order = $creditmemo->getOrder();

                // get the invoices
                $invoices = $order->getInvoiceCollection();
                if ($invoices->count() == 1)
                {
                    // get the invoice
                    $invoice = $invoices->getFirstItem();
                    $creditmemo->setInvoiceId($invoice->getData('entity_id'));
                    $creditmemo->setInvoice($invoice);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        // return
        return [ $creditmemo ];
    }
}