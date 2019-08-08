<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 8/6/19
 * Time: 3:27 PM
 */

namespace SMG\Sales\Plugin\Model\Order;

use Psr\Log\LoggerInterface;

class Creditmemo
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function afterGetInvoice(\Magento\Sales\Model\Order\Creditmemo $subject, $result)
    {
        try
        {
            if ($result)
            {
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order = $subject->getOrder();

                // get the invoices
                $invoices = $order->getInvoiceCollection();
                if ($invoices->count() == 1)
                {
                    // get the invoice
                    $invoice = $invoices->getFirstItem();
                    $subject->setInvoiceId($invoice->getData('entity_id'));
                    $subject->setInvoice($invoice);
                    $result = $invoice;
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