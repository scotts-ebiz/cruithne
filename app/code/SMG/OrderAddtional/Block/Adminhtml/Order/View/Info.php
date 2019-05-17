<?php
namespace SMG\OrderAddtional\Block\Adminhtml\Order\View;
class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{
	protected $orderRepository;
	protected $sapOrderFactory;
    protected $transactionRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $sapOrderItemCollectionFactory;
    /**
     * Customer service
     *
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $metadata;

    /**
     * Group service
     *
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Metadata element factory
     *
     * @var \Magento\Customer\Model\Metadata\ElementFactory
     */
    protected $_metadataElementFactory;

    /**
     * @var Address\Renderer
     */
    protected $addressRenderer;
    /**
     * @var ShippingConditionCodeFactory
     */
    protected $_shippingConditionCodeFactory;

    /**
     * @var ShippingConditionCodeResource
     */
    protected $_shippingConditionCodeResource;
    
	public function __construct(
	    \Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\SMG\Sap\Model\ResourceModel\sapOrder $sapOrder,
		\Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
		\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
		\Magento\Framework\Api\FilterBuilder $filterBuilder,
		\SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory $sapOrderItemCollectionFactory,
		\SMG\OfflineShipping\Model\ShippingConditionCodeFactory $shippingConditionCodeFactory,
		\SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode $shippingConditionCodeResource,
		 array $data = []
	){
		 $this->groupRepository = $groupRepository;
        $this->metadata = $metadata;
        $this->_metadataElementFactory = $elementFactory;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $registry, $adminHelper, $groupRepository, $metadata, $elementFactory, $addressRenderer, $data);
		$this->orderRepository = $orderRepository;
		$this->sapOrderFactory = $sapOrder;	
		$this->transactionRepository = $transactionRepository;	
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->filterBuilder = $filterBuilder;
		$this->sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
		$this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
	}
	
	public function getOrderInfo()
    {
		
		$dataobj = [];
		$dataobj['sap_order_id']=NULL;
		$dataobj['order_status']=NULL;
		$dataobj['sap_billing_doc_number']=NULL;
		$dataobj['additional_information']=NULL;
		$dataobj['cc_authorization_transaction'] = NULL;
        $dataobj['cc_capture_transaction'] = NULL;
        $dataobj['cc_response'] = NULL;
        $dataobj['shipping_description'] = NULL;
        $dataobj['sap_shipping_code'] = NULL;
        $dataobj['tracking'] = NULL;
        $dataobj['delivery_number'] = NULL;
        $dataobj['invoice_id'] = NULL;
		$tracking = [];
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $dataobj['order_id'] = $order->getRealOrderId();
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
		
        $dataobj['sap_order_id'] = $sapOrder->getSapOrderId();
        $dataobj['order_status'] = $sapOrder->getOrderStatus();
        $dataobj['sap_billing_doc_number'] = $sapOrder->getSapBillingDocNumber();
        $dataobj['additional_information'] = json_encode($order->getPayment()->getAdditionalInformation());

        if($transactionList){
        foreach($transactionList as $transactionObj)
        {
			if($transactionObj->getTxnType() == "authorization"){
			 $dataobj['cc_authorization_transaction'] = $transactionObj->getTxnId();
		    }
		    if($transactionObj->getTxnType() == "capture"){
			 $dataobj['cc_capture_transaction'] = $transactionObj->getTxnId();
		    }
		    $response = json_encode($transactionObj->getAdditionalInformation());
		    $objresponse = json_decode($response);
		    if($objresponse->{'raw_details_info'}->{'response'} == '000'){
		     $dataobj['cc_response'] = 'Approved';
		    }
		    else{
			 $dataobj['cc_response'] = 'Declined';
			}
		    
		}
       }
        
        $dataobj['shipping_description'] = $order->getShippingDescription();
        $dataobj['sap_shipping_code'] = $shippingCondition->getData('sap_shipping_method');
        if($dataobj['sap_order_id'] != NULL)
        {
			$sapItemCollection = $sapOrderItem->addFieldToSelect('ship_tracking_number')->addFieldToFilter('order_sap_id', $orderId);
			foreach($sapItemCollection as $sapItemCollectionObj)
			{
				$tracking[]=$sapItemCollectionObj->getShipTrackingNumber();
			}
			if(count($tracking) > 1)
			{
				$dataobj['tracking'] = implode(',',$tracking);
			}
			else
			{
				$dataobj['tracking'] = implode(',',$tracking);
			}
			
		}
        
        $dataobj['delivery_number'] = $sapOrder->getDeliveryNumber();
        
        return $dataobj;
    }
}
