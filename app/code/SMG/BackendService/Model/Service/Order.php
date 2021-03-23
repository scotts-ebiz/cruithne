<?php

namespace SMG\BackendService\Model\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use SMG\BackendService\Model\Client\Api;
use SMG\BackendService\Helper\Data as Config;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Customer\Model\AddressFactory;
use \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TrasactionResultInterface;
use \Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Order
{

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentItemRepositoryInterface
     */
    protected $shipmentItem;

    /**
     * @var Api
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var  AddressFactory
     */
    private $addressFactory;

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

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Order constructor.
     * @param Api $client
     * @param Config $config
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentItemRepositoryInterface $shipmentItemRepositoryInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressFactory $addressFactory
     * @param TrasactionResultInterface $trasactionResultInterface
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Api $client,
        Config $config,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentItemRepositoryInterface $shipmentItem,
        OrderRepositoryInterface $orderRepository,
        AddressFactory $addressFactory,
        TrasactionResultInterface $trasactionResultInterface,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentItem = $shipmentItem;
        $this->addressFactory = $addressFactory;
        $this->trasactionResultInterface = $trasactionResultInterface;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $orders
     * @param string $applicationWindowStartDate
     * @param string $applicationWindowEndDate
     * @param string $seasonname
     */
    public function postOrderService(
        $orders,
        $applicationWindowStartDate = '',
        $applicationWindowEndDate = '',
        $seasonname = ''
    ) {
        //check module status active or not
        if($this->config->getStatus()){

            $orderId = $orders->getId();
            $order = $this->orderRepository->get($orderId);
            $lsOrderId = $order->getData('ls_order_id');
            if (!isset($lsOrderId)) {
                $response = $this->client->execute(
                    $this->config->getOrderApiUrl(),
                    "orders",
                    $this->buildOrderObject($order, $applicationWindowStartDate, $applicationWindowEndDate, $seasonname),
                    Request::HTTP_METHOD_POST
                );

                if ($response == false) {

                    $this->logger->info("Order Service with no response for orderId : " . $orderId);

                } else {

                    $this->addResponseOrder($order, $response);

                }
            }
        }

        return;
    }

    /**
     * @param $order
     * @param string $applicationWindowStartDate
     * @param string $applicationWindowEndDate
     * @param string $seasonname
     * @return array
     */
    public function buildOrderObject(
        $order,
        $applicationWindowStartDate = '',
        $applicationWindowEndDate = '',
        $seasonname = ''
    ) {
        $transaction = $this->trasactionResultInterface->create()->addOrderIdFilter($order->getId())->getFirstItem();
        $transactionId = $transaction->getData('txn_id');
        $urlParts = parse_url($order->getStore()->getBaseUrl());
        $payment = $order->getPayment();
        $additionalInfo = ($payment->getAdditionalInformation());
        $cc_type = $this->config->getCardFullName($payment->getData('cc_type'));
        $shippingData = $order->getShippingAddress()->getData();
        $billingData = $order->getBillingAddress()->getData();
        $billing_customer_address_id = $order->getBillingAddress()->getData('customer_address_id');
        $shipping_customer_address_id = $order->getShippingAddress()->getData('customer_address_id');
        $billingCreatedAt = '';
        $billingUpdatedAt = '';
        $shippingCreatedAt = '';
        $shippingUpdatedAt = '';

        if ($billing_customer_address_id != 0 || $billing_customer_address_id != null) {
            $billingaddressObject = $this->addressFactory->create()->load($billing_customer_address_id);
            $billingCreatedAt = $billingaddressObject->getData('created_at');
            $billingUpdatedAt = $billingaddressObject->getData('updated_at');
        }

        if ($shipping_customer_address_id != 0 || $shipping_customer_address_id != null) {
            $shippingaddressObject = $this->addressFactory->create()->load($shipping_customer_address_id);
            $shippingCreatedAt = $shippingaddressObject->getData('created_at');
            $shippingUpdatedAt = $shippingaddressObject->getData('updated_at');
        }

        $params = [];

        $params['transId'] = $this->config->generateUuid();
        $params['sourceService'] = 'WEB';
        $params['externalId'] = $order->getId();
        $params['incrementId'] = $order->getIncrementId();
        $params['paymentUuid'] = $transactionId;
        $params['cartType'] = 'M2';
        $params['orderType'] = 'WEB';
        $params['customerId'] = $order->getCustomerId();
        $params['firstName'] = $billingData['firstname'];
        $params['lastName'] = $billingData['lastname'];
        $params['email'] = $order->getCustomerEmail();
        $params['phone'] = $billingData['telephone'];
        $params['billingAddress']['addressId'] = ($billing_customer_address_id != null ? $billing_customer_address_id : 0);
        $params['billingAddress']['street1'] = $order->getBillingAddress()->getStreet(1)[0];
        $params['billingAddress']['street2'] = $order->getBillingAddress()->getStreet(1)[0] != $order->getBillingAddress()->getStreet(2)[0] ? $order->getBillingAddress()->getStreet(2) : '';
        $params['billingAddress']['city'] = $billingData['city'];
        $params['billingAddress']['region'] = $billingData['region'];
        $params['billingAddress']['country'] = $billingData['country_id'];
        $params['billingAddress']['postalCode'] = $billingData['postcode'];
        $params['billingAddress']['updatedAt'] = $billingCreatedAt;
        $params['billingAddress']['createdAt'] = $billingUpdatedAt;
        $params['billingAddress']['firstName'] = $billingData['firstname'];
        $params['billingAddress']['lastName'] = $billingData['lastname'];
        $params['billingAddress']['phone'] = $billingData['telephone'];

        $params['shippingAddress']['addressId'] = ($shipping_customer_address_id != null ? $shipping_customer_address_id : 0);;
        $params['shippingAddress']['street1'] = $order->getShippingAddress()->getStreet(1)[0];
        $params['shippingAddress']['street2'] = $order->getShippingAddress()->getStreet(1)[0] != $order->getShippingAddress()->getStreet(2)[0] ? $order->getShippingAddress()->getStreet(2)[0] : '';
        $params['shippingAddress']['city'] = $shippingData['city'];
        $params['shippingAddress']['region'] = $shippingData['region'];
        $params['shippingAddress']['country'] = $shippingData['country_id'];
        $params['shippingAddress']['postalCode'] = $shippingData['postcode'];
        $params['shippingAddress']['updatedAt'] = $shippingCreatedAt;
        $params['shippingAddress']['createdAt'] = $shippingUpdatedAt;
        $params['shippingAddress']['firstName'] = $shippingData['firstname'];
        $params['shippingAddress']['lastName'] = $shippingData['lastname'];
        $params['shippingAddress']['phone'] = $shippingData['telephone'];

        $i = 0;
        foreach ($order->getAllItems() as $item) {
            $product = $this->productRepository->getById($item->getProductId());
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
            $params['products'][$i]['generatedProductId'] = '';
            $params['products'][$i]['coverage'] = '';
            $params['products'][$i]['description'] = $product->getDescription();
            $imageUrl = $this->storeManager->getStore($product->getStoreId())
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA ). 'catalog/product';
            $thumbnailUrl = $imageUrl . ($product->getThumbnail() ?? '');
            $params['products'][$i]['thumbnailImage'] = $thumbnailUrl;
            $params['products'][$i]['shortDescription'] = $product->getShortDescription();
            $params['products'][$i]['thumbnailLabel'] = $product->getThumbnailLabel();
            $params['products'][$i]['shipStartDate'] = $order->getData('ship_start_date');
            $params['products'][$i]['shipEndDate'] = $order->getData('ship_end_date');
            $i++;
        }

        $params['shipments'] =  $this->getShipmentInfo($order);

        $params['shippingCondition'] = $order->getShippingDescription();
        $params['shippingCost'] = $order->getShippingAmount();
        $params['tax'] = $order->getTaxAmount();
        $params['subTotal'] = $order->getSubtotal();
        $params['discounts'] = $order->getDiscountAmount();
        $params['total'] = $order->getGrandTotal();
        $params['createdAt'] = $order->getData('created_at');
        $params['updatedAt'] = $order->getData('updated_at');
        $params['pricingDate'] = $order->getData('created_at');
        $params['createInvoice'] = false;
        $params['sendEmail'] = true;
        $params['shippingMethod'] = $order->getShippingMethod();;
        $params['couponCode'] = $order->getCouponCode();

        $params['websiteUrl'] = $order->getCouponCode();
        if ($urlParts['host']) {
            $array = explode('.', $urlParts['host']);
            $count = count($array);
            if ($count > 1) {
                $params['websiteUrl'] = strtoupper($array[$count - 2].'.'.$array[$count - 1]);
            }
        }


        $params['createGuestCustomer'] = true;
        $params['doNotSaveExternally'] = true;
        if(isset($additionalInfo['last_four'])){
            $last4 = $additionalInfo['last_four'];
        }else{
            $last4 = '';
        }
        $params['cc_type'] = $cc_type;
        $params['ccLast4'] = $last4;

        $params['parentOrderId'] = $order->getParentOrderId();
        $params['orderId'] = $order->getLsOrderId();
        $params['subType'] = $order->getSubType();
        $params['recurlyPlanCode'] = $order->getRecurlyPlanCode();
        $params['recurlyId'] = $order->getRecurlyId();
        $params['shippingStartDate'] = $order->getShipStartDate();
        $params['shippingEndDate'] = $order->getShipEndDate();
        $params['paid'] = '';
        $params['price'] = '';
        $params['masterSubscriptionTaxAmount'] = '';
        $params['masterSubscriptionDiscountAmount'] = '';
        $params['invoiceTaxRate'] = '';
        $params['orderStatus'] = $order->getStatus();
        $params['cancellationDate'] = '';
        $params['completedQuizId'] = '';
        $params['invoiceTaxRegion'] = '';
        $params['recommendationId'] = '';
        $params['m2MasterSubscriptionId'] = '';
        $params['m2SubscriptionId'] = '';
        $params['hdrDiscFixedAmount'] = '';
        $params['hdrDiscPerc'] = '';
        $params['hdrDiscCondCode'] = '';
        $params['ccType'] = $cc_type;
        $params['lawnZone'] = '';
        $params['intervalDbEntityId'] = '';
        $params['migrated'] = '';

        $this->logger->info("OrderService Request :",$params);
        return $params;
    }


    /**
     * @param $order
     */
    public function getShipmentInfo($order) {
        $shipmentCollection = $order->getShipmentsCollection();
        $j=0;
        $data = array();
        foreach ($shipmentCollection as $shipment) {
            $data[$j]['generatedShipmentId'] = $shipment->getData('order_id');
            $data[$j]['orderId'] = $shipment->getData('order_id');
            $data[$j]['shippingId'] = $shipment->getData('order_id');
            $data[$j]['trackingNumber'] = $shipment->getData('track_number');

            foreach($shipment->getItems() as $sItem)
            {
                $data[$j]['shipmentItems'][$sItem->getData('product_id')]['generatedShipmentItemId'] = $shipment->getData('order_id');
                $data[$j]['shipmentItems'][$sItem->getData('product_id')]['shipmentId'] = $shipment->getData('entity_id');
                $data[$j]['shipmentItems'][$sItem->getData('product_id')]['sku'] = $sItem->getData('sku');
                $data[$j]['shipmentItems'][$sItem->getData('product_id')]['qty'] = $sItem->getData('qty');
                $data[$j]['shipmentItems'][$sItem->getData('product_id')]['createdAt'] = $shipment->getData('created_at');
                $data[$j]['shipmentItems'][$sItem->getData('product_id')]['updatedAt'] = $shipment->getData('updated_at');
            }
            $data[$j]['createdAt'] = $shipment->getData('created_at');
            $data[$j]['updatedAt'] = $shipment->getData('updated_at');
            $j++;
        }

        return $data;
    }

    public function postOrderCommentNote(
    $orderId,
    $noteMessage
    ) {
       //check module status active or not
       if($this->config->getStatus()){

        $params['transId'] = $this->config->generateUuid();
        $params['sourceService'] = 'WEB';
        $params['orderId'] = $orderId;
        $params['noteType'] = 'email';
        $params['noteMessage'] = $noteMessage;
        $params['condition'] = 'success';

        $this->logger->info("OrderService Request Note:",$params);

        $response = $this->client->execute(
        $this->config->getOrderApiUrl(),
        "orders/OrdersController_createOrderNote",
        $params,
        Request::HTTP_METHOD_POST
        );

        if ($response == false) {
        $this->logger->info("Order Service with no response for orderId on order comment note: ".$orderId);
        }

       }
        return;
    }

    /**
    * @param $orderId
    * @param $response
    */
    public function addResponseOrder(
    $order,
    $responseObj
    ) {

        $response = json_decode($responseObj);
        $orderId = $order->getId();
        $order->setData('ls_order_id', property_exists($response, 'orderId') ? $response->{'orderId'} : "");
        $order->setData('parent_order_id', property_exists($response, 'parentOrderId') ? $response->{'parentOrderId'} : "");
        $order->setData('order_type', property_exists($response, 'orderType') ? $response->{'orderType'} : "");
        $order->setData('scotts_customer_id', property_exists($response, 'customerId') ? $response->{'customerId'} : "");
         try {
            $this->orderRepository->save($order);
            $this->logger->info("Response API data store in orderId: ".$orderId);
        } catch (\Exception $ex) {
           $this->logger->info("Failed to store Response API data in orderId: ".$orderId);
        }
    }

    /**
    * @param $orderId
    * @param $reason
    */
    public function cancelOrderSubcription(
    $orderId,
    $status
    ) {

        //check module status active or not
        if($this->config->getStatus()){

            $params['transId'] = $this->config->generateUuid();
            $params['sourceService'] = 'WEB';
            $params['canceledOrders']['orderId'] = $orderId;
            $params['canceledOrders']['status'] = $status;
            $params['canceledOrders']['canceledAt'] = date('m-d-Y H:i:s');
            $this->logger->info("CancelOrderSubcription Request Note:",$params);

            $response = $this->client->execute(
            $this->config->getSubcriptionApiUrl(),
            "subscriptions/SubscriptionsController_destroy",
            $params,
            Request::HTTP_METHOD_POST
            );

            if ($response == false) {
            $this->logger->info("Order Service with no response for orderId on cancel order subcription: ".$orderId);
            }

        }
        return;
    }
}
