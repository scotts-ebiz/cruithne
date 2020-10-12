<?php

namespace SMG\BackendService\Model\Service;

use SMG\BackendService\Model\Client\Api;
use SMG\BackendService\Helper\Data as Config;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Customer\Model\AddressFactory;
use \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TrasactionResultInterface;
use \Psr\Log\LoggerInterface;

class Order
{
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
     * Order constructor.
     * @param Api $client
     * @param Config $config
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressFactory $addressFactory
     * @param TrasactionResultInterface $trasactionResultInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        Api $client,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        AddressFactory $addressFactory,
        TrasactionResultInterface $trasactionResultInterface,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->trasactionResultInterface = $trasactionResultInterface;
        $this->logger = $logger;
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
        $orderId = $orders->getId();
        $order = $this->orderRepository->get($orderId);
        $response = $this->client->execute(
            $this->config->getOrderApiUrl(),
            "orders",
            $this->buildOrderObject($order, $applicationWindowStartDate, $applicationWindowEndDate, $seasonname),
            Request::HTTP_METHOD_POST
        );

        if ($response == false) {
           $this->logger->info("Order Service with no response for orderId : ".$orderId);
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
        $methodname = $payment->getMethod();
        $additionalInfo = ($payment->getAdditionalInformation());
        $last4 = $additionalInfo['last_four'];
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

        $items = array();
        $params = array();

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

        $params['shippingAddress']['addressId'] = ($shipping_customer_address_id != null ? $shipping_customer_address_id : 0);;
        $params['shippingAddress']['street1'] = $order->getShippingAddress()->getStreet(1)[0];
        $params['shippingAddress']['street2'] = $order->getShippingAddress()->getStreet(1)[0] != $order->getShippingAddress()->getStreet(2)[0] ? $order->getShippingAddress()->getStreet(2)[0] : '';
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
        $params['sendEmail'] = ($order->getSendEmail() != null ? 'true' : 'false');
        $params['shippingMethod'] = $order->getShippingMethod();;
        $params['couponCode'] = $order->getCouponCode();
        $params['websiteUrl'] = $urlParts['host'];
        $params['createGuestCustomer'] = 'true';
        $params['doNotSaveExternally'] = 'true';
        $params['last_4'] = $last4;
        $params['cc_type'] = $cc_type;
        $this->logger->info("OrderService Request :",$params);
        return $params;
    }
    
        /**
        * @param $id
        * @param string $noteMessage
        */
        public function postOrderCommentNote(
        $orderId,
        $noteMessage
        ) {

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

            return;
        }
}