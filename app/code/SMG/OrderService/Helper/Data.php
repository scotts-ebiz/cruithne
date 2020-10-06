<?php

namespace SMG\OrderService\Helper;

use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\Helper\AbstractHelper;
use \GuzzleHttp\Client;
use \GuzzleHttp\ClientFactory;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\ResponseFactory;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Customer\Model\AddressFactory;
use \Magento\Framework\Controller\ResultFactory;
use \Psr\Log\LoggerInterface;
use \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TrasactionResultInterface;

class Data extends AbstractHelper
{
	
	 const API_REQUEST_URI = '//cts-orders-k6x4iqtnzq-uc.a.run.app/v1/orders';

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var  AddressFactory
     */
    private  $addressFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
	 private $logger;
	 
	 /**
     * @var TrasactionResultInterface
     */
    private $trasactionResultInterface;
	
    public function __construct(
        Context $context,
		ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        ResultFactory $resultFactory,
        OrderRepositoryInterface $orderRepository,
        AddressFactory  $addressFactory,
        LoggerInterface $logger,
		TrasactionResultInterface $trasactionResultInterface
    ) {
        parent::__construct($context);
		$this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->resultFactory = $resultFactory;
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
		$this->trasactionResultInterface = $trasactionResultInterface;
    }

    public function postOrderService($orders,$applicationWindowStartDate = '',$applicationWindowEndDate = '',$seasonname = '')
    {
		$client = $this->clientFactory->create(['config' => [
            'base_uri' => self::API_REQUEST_URI
        ]]);
   
        $orderId = $orders->getId();
		$order = $this->orderRepository->get($orderId);
		
		$transaction = $this->trasactionResultInterface->create()->addOrderIdFilter($orderId)->getFirstItem();
        $transactionId = $transaction->getData('txn_id');
        $urlParts = parse_url($order->getStore()->getBaseUrl());
		$payment = $order->getPayment();
		$methodname = $payment->getMethod();
		$shippingData = $order->getShippingAddress()->getData();
        $billingData = $order->getBillingAddress()->getData();
		$billing_customer_address_id = $order->getBillingAddress()->getData('customer_address_id');
		$shipping_customer_address_id = $order->getShippingAddress()->getData('customer_address_id');
		$billingCreatedAt='';
		$billingUpdatedAt='';
		$shippingCreatedAt='';
		$shippingUpdatedAt='';
		
		if($billing_customer_address_id!=0 || $billing_customer_address_id!=null)
		{
			$billingaddressObject = $this->addressFactory->create()->load($billing_customer_address_id);
			$billingCreatedAt = $billingaddressObject->getData('created_at');
			$billingUpdatedAt = $billingaddressObject->getData('updated_at');
		}
		
		if($shipping_customer_address_id!=0 || $shipping_customer_address_id!=null)
		{
			$shippingaddressObject = $this->addressFactory->create()->load($shipping_customer_address_id);
			$shippingCreatedAt = $shippingaddressObject->getData('created_at');
			$shippingUpdatedAt = $shippingaddressObject->getData('updated_at');
		}
		
		$items = array();		
		$params = array();
		
		$params['transId'] = $this->generate_uuid();
		$params['sourceService'] = 'WEB';
		$params['externalId'] = $orderId;
		$params['incrementId'] = $order->getIncrementId();
		$params['paymentUuid'] = $transactionId;
		$params['cartType'] = 'M2';
		$params['orderType'] = 'WEB';
		$params['customerId'] = $order->getCustomerId();
		$params['firstName'] = $billingData['firstname'];
		$params['lastName'] = $billingData['lastname'];
		$params['email'] = $order->getCustomerEmail();
		$params['phone'] = $billingData['telephone'];
		$params['billingAddress']['addressId'] = ($billing_customer_address_id !=null ? $billing_customer_address_id : 0);
		$params['billingAddress']['street1'] = $order->getBillingAddress()->getStreet(1)[0];
		$params['billingAddress']['street2'] = $order->getBillingAddress()->getStreet(1)[0] !=$order->getBillingAddress()->getStreet(2)[0] ? $order->getBillingAddress()->getStreet(2) : '' ;
		$params['billingAddress']['city'] = $billingData['city'];
		$params['billingAddress']['region'] = $billingData['region'];
		$params['billingAddress']['country'] = $billingData['country_id'];
		$params['billingAddress']['postalCode'] = $billingData['postcode'];
		$params['billingAddress']['updatedAt'] = $billingCreatedAt;
		$params['billingAddress']['createdAt'] = $billingUpdatedAt;
		
		$params['shippingAddress']['addressId'] = ($shipping_customer_address_id !=null ? $shipping_customer_address_id : 0);;
		$params['shippingAddress']['street1'] = $order->getShippingAddress()->getStreet(1)[0];
		$params['shippingAddress']['street2'] = $order->getShippingAddress()->getStreet(1)[0] !=$order->getShippingAddress()->getStreet(2)[0] ? $order->getShippingAddress()->getStreet(2)[0] : '' ;
		$params['shippingAddress']['city'] = $shippingData['city'];
		$params['shippingAddress']['region'] = $shippingData['region'];
		$params['shippingAddress']['country'] = $shippingData['country_id'];
		$params['shippingAddress']['postalCode'] = $shippingData['postcode'];
		$params['shippingAddress']['updatedAt'] = $shippingCreatedAt;
		$params['shippingAddress']['createdAt'] = $shippingUpdatedAt;
		
		$i = 0;
		foreach ($order->getAllItems() as $item) {
		 $params['products'][$i]['productId'] = $item->getProductId();
		 $params['products'][$i]['sku'] = $item->getSku();
		 $params['products'][$i]['productName'] = $item->getName();
		 $params['products'][$i]['qty'] = $item->getQtyOrdered();
		 $params['products'][$i]['price'] = $item->getPrice();
		 $params['products'][$i]['extendedPrice'] = $item->getPrice();
		 $params['products'][$i]['productTotal'] = $item->getRowTotal();
		 $params['products'][$i]['taxAmount'] = $item->getTaxAmount();
		 $params['products'][$i]['originalPrice'] = $item->getOriginalPrice();
		 $params['products'][$i]['parentPrice'] = $item->getPrice();
		 $params['products'][$i]['productType'] = $item->getProductType();
		 $params['products'][$i]['shippingStartDate'] = $order->getData('ship_start_date');
		 $params['products'][$i]['shippingEndDate'] = $order->getData('ship_end_date');
		 $params['products'][$i]['applicationWindowStartDate'] = $applicationWindowStartDate;
		 $params['products'][$i]['applicationWindowEndDate'] = $applicationWindowEndDate;
		 $params['products'][$i]['season'] = $seasonname;
		 $i++;
		}
		
		$params['shippingCondition'] = $order->getShippingDescription();
		$params['shippingCost'] = $order->getShippingAmount();
		$params['tax'] = $order->getTaxAmount();
		$params['subTotal'] = $order->getSubtotal();
		$params['discounts'] = $order->getDiscountAmount();
		$params['total'] = $order->getGrandTotal();
		$params['createdAt'] = $order->getData('created_at');
		$params['updatedAt'] = $order->getData('updated_at');
		$params['pricingDate'] = $order->getData('created_at');
		$params['createInvoice'] = ($order->hasInvoices() ? 'true' : 'false');
		$params['sendEmail'] = ($order->getSendEmail()!=null ? 'true' : 'false');
		$params['shippingMethod'] = $order->getShippingMethod();;
		$params['couponCode'] = $order->getCouponCode();
		$params['websiteUrl'] = $urlParts['host'];
		$params['createGuestCustomer'] = 'true';
		$params['doNotSaveExternally'] = 'true';
		
		
		$this->logger->info("OrderService Request :",$params);
        
		try {
            $response = $client->request(
                Request::HTTP_METHOD_POST,
                $params
            );
         $this->logger->info("Order Service response : ",$response->getBody());
        } catch (\Exception $ex) {
            $this->logger->info("Order Service with no response for orderId : ".$orderId);
        }
		return;
    }
	
	function generate_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0C2f ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0x2Aff ), mt_rand( 0, 0xffD3 ), mt_rand( 0, 0xff4B )
		);
	}
}