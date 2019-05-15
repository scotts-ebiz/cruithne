<?php
namespace SMG\OrderAddtional\Block\Adminhtml\Invoice\View;

class VantivInvoice extends \Magento\Backend\Block\Template
{
	protected $orderRepository;
	protected $sapOrderFactory;
    protected $invoice;
    protected $transactionRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $sapOrderItemCollectionFactory;
    protected $_shippingConditionCodeFactory;
    protected $_shippingConditionCodeResource;
    
	public function __construct(
	    \Magento\Backend\Block\Template\Context $context,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Sales\Model\Order\Invoice $invoice,
		\SMG\Sap\Model\ResourceModel\sapOrder $sapOrder,
		\Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
		\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
		\Magento\Framework\Api\FilterBuilder $filterBuilder,
		\SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory $sapOrderItemCollectionFactory,
		\SMG\OfflineShipping\Model\ShippingConditionCodeFactory $shippingConditionCodeFactory,
		\SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode $shippingConditionCodeResource,
		 array $data = []
	){
		 parent::__construct($context, $data);
		$this->orderRepository = $orderRepository;
		$this->sapOrderFactory = $sapOrder;
		$this->invoice = $invoice;
		$this->transactionRepository = $transactionRepository;	
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->filterBuilder = $filterBuilder;
		$this->sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
		$this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
		
	}
	
	public function getOrderInfo()
    {
		$data = [];
		$data['sap_order_id']=NULL;
		$data['order_status']=NULL;
		$data['sap_billing_doc_number']=NULL;
		$data['additional_information']=NULL;
		$data['cc_authorization_transaction'] = NULL;
        $data['cc_capture_transaction'] = NULL;
        $data['cc_response'] = NULL;
        $data['shipping_description'] = NULL;
        $data['sap_shipping_code'] = NULL;
        $data['tracking'] = NULL;
        $data['delivery_number'] = NULL;
        $data['invoice_id'] = NULL;
		$tracking = [];
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $invoice = $this->invoice->load($invoiceId);
        $orderId = $invoice->getOrderId();
        $order = $this->orderRepository->get($orderId);
        $data['order_id'] = $order->getRealOrderId();
        $sapOrder = $this->sapOrderFactory->getSapOrderByOrderId($orderId);

        $filters[] = $this->filterBuilder->setField('payment_id')
        ->setValue($order->getPayment()->getId())
        ->create();
        $filters[] = $this->filterBuilder->setField('order_id')
        ->setValue($orderId)
        ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
        ->create();

        $transactionList = $this->transactionRepository->getList($searchCriteria);
        $sapOrderItem = $this->sapOrderItemCollectionFactory->create();
        $shippingCondition = $this->_shippingConditionCodeFactory->create();
        $this->_shippingConditionCodeResource->load($shippingCondition, $order->getShippingMethod(), 'shipping_method');
		
        $data['sap_order_id'] = $sapOrder->getSapOrderId();
        $data['order_status'] = $sapOrder->getOrderStatus();
        $data['sap_billing_doc_number'] = $sapOrder->getSapBillingDocNumber();
        $data['additional_information'] = json_encode($order->getPayment()->getAdditionalInformation());
        $data['cc_authorization_transaction'] = '';
        $data['cc_capture_transaction'] = '';
        $data['cc_response'] = '';
        if($transactionList){
        foreach($transactionList as $transactionObj)
        {
			if($transactionObj->getTxnType() == "authorization"){
			 $data['cc_authorization_transaction'] = $transactionObj->getTxnId();
		    }
		    if($transactionObj->getTxnType() == "capture"){
			 $data['cc_capture_transaction'] = $transactionObj->getTxnId();
		    }
		    $response = json_encode($transactionObj->getAdditionalInformation());
		    $objresponse = json_decode($response);
		    if($objresponse->{'raw_details_info'}->{'response'} == '000'){
		     $data['cc_response'] = 'Approved';
		    }
		    else{
			 $data['cc_response'] = 'Declined';
			}
		    
		}
       }
        
        $data['shipping_description'] = $order->getShippingDescription();
        $data['sap_shipping_code'] = $shippingCondition->getData('sap_shipping_method');
        if($data['sap_order_id'] != NULL)
        {
			$sapItemCollection = $sapOrderItem->addFieldToSelect('ship_tracking_number')->addFieldToFilter('order_sap_id', $orderId);
			foreach($sapItemCollection as $sapItemCollectionObj)
			{
				$tracking[]=$sapItemCollectionObj->getShipTrackingNumber();
			}
			if(count($tracking) > 1)
			{
				$data['tracking'] = implode(',',$tracking);
			}else
			{
				$data['tracking'] = implode(',',$tracking);
			}
			
		}
        
        $data['delivery_number'] = $sapOrder->getDeliveryNumber();
        return $data;
    }
	
}
