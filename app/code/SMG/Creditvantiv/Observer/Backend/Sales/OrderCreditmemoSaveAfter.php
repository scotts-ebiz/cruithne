<?php
namespace SMG\Creditvantiv\Observer\Backend\Sales;
use Magento\Framework\Event\ObserverInterface;

//class OrderCreditmemoSaveAfter implements ObserverInterface
class OrderCreditmemoSaveAfter implements ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
     
    private $order;
    public function __construct(\Magento\Sales\Model\Order $order) {
    $this->order = $order;
    }  


    public function execute(\Magento\Framework\Event\Observer $observer)
    { 
        $creditmemo     = $observer->getEvent()->getCreditmemo();
     

        $order_id       = $creditmemo->getOrderId();
        $order          = $this->order->load($order_id);
        $total_refunded = $order->getTotalRefunded();
        $credit_id      = $creditmemo->getId();
        $credit_note    = $creditmemo->getCustomerNote();
        $createat       = $order->getCreatedAt();
       

        try{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $creditpayment = $objectManager->create('SMG\Creditvantiv\Model\Payment');
            $creditpayment->setOrderId($order_id);
            $creditpayment->setCreditId($credit_id);
            $creditpayment->setIsCapture(1);
            $creditpayment->setCaptureProcessDate($createat);
            $creditpayment->setCreditNote($credit_note);
            $creditpayment->setCreditAmount($total_refunded);
            $creditpayment->setStatus('pending');
            
            $creditpayment->save();
        }catch(Exception $e){
           
            echo $e->getMessage();
        }
       

    }
}
