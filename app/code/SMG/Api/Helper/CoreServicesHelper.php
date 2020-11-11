<?php

namespace SMG\Api\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogSearch\Model\AdvancedFactory;
use Magento\CatalogSearch\Model\ResourceModel\Advanced as AdvancedResource;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\DB\Transaction as Transaction;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender as InvoiceSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\Service\InvoiceService as InvoiceService;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\SubscriptionApi\Model\Subscription;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderItemFactory;
use SMG\SubscriptionApi\Model\SubscriptionFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderItemFactory;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use SMG\Api\Helper\ShipmentHelper;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use SMG\BackendService\Helper\Data as Config;

class CoreServicesHelper
{
    /** @var LoggerInterface */
    protected $_logger;

    /** @var ResponseHelper */
    protected $_responseHelper;

    /** @var OrderFactory */
    protected $_orderFactory;

    /** @var OrderResource */
    protected $_orderResource;

    /** @var DiscountHelper */
    protected $_discountHelper;

    /** @var InvoiceService */
    protected $_invoiceService;

    /** @var Transaction */
    protected $_transaction;

    /** @var InvoiceSender */
    protected $_invoiceSender;

    /** @var CustomerResource */
    protected $_customerResource;

    /** @var CustomerFactory */
    protected $_customerFactory;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var QuoteManagement */
    protected $_quoteManagement;

    /** @var QuoteFactory */
    protected $_quoteFactory;

    /** @var QuoteResource */
    protected $_quoteResource;

    /** @var AddressRepositoryInterface */
    protected $_addressRepository;

    /** @var ProductFactory */
    protected $_productFactory;

    /** @var ProductResource */
    protected $_productResource;

    /**@var AdvancedFactory */
    protected $_advancedFactory;

    /** @var AdvancedResource */
    protected $_advancedResource;

    /** @var ProductRepository */
    protected $_productRepository;

    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;

    /** @var SubscriptionFactory */
    protected $_subscriptionFactory;

    /** @var SubscriptionResource */
    protected $_subscriptionResource;

    /** @var SubscriptionOrderFactory */
    protected $_subscriptionOrderFactory;

    /** @var SubscriptionOrderItemFactory */
    protected $_subscriptionOrderItemFactory;

    /** @var SubscriptionAddonOrderFactory */
    protected $_subscriptionAddonOrderFactory;

    /** @var SubscriptionAddonOrderItemFactory */
    protected $_subscriptionAddonOrderItemFactory;

    /** @var RecurlyHelper */
    protected $_recurlyHelper;

    /** @var SubscriptionOrderResource */
    protected $_subscriptionOrderResource;

    /** @var string */
    protected $_loggerPrefix;

    /** @var ItemResource */
    protected $_itemResource;

    /** @var \Magento\Quote\Model\Quote\AddressFactory */
    private $_addressFactory;

    /** @var OrderRepositoryInterface */
    private $_orderRepository;

    /** @var ShippingConditionCodeFactory */
    protected $_shippingConditionCodeFactory;

    /** @var ShippingConditionCodeResource */
    protected $_shippingConditionCodeResource;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var ShipOrderInterface
     */
    protected $_shipOrderInterface;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    protected $_shipmentItemCreationInterfaceFactory;

    /**
     * @var ShipmentTrackCreationInterfaceFactory
     */
    protected $_shipmentTrackCreationInterfaceFactory;

    /**
     * @var ShipmentHelper
     */
    protected $_shipmentHelper;

     /**
     * @var OrderItemCollectionFactory
     */
    protected $_orderItemCollectionFactory;
    
    /**
     * @var Config
     */
    private $config;

    CONST IMAGE_URL = 'https://images.scottsprogram.com/prod/pub/media/catalog/product';

    /**
     * OrderStatusHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param DiscountHelper $discountHelper
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param CustomerResource $customerResource
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param QuoteManagement $quoteManagement
     * @param QuoteFactory $quoteFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param AdvancedFactory $advancedFactory
     * @param AdvancedResource $advancedResource
     * @param AddressFactory $addressFactory
     * @param ProductRepository $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteResource $quoteResource
     * @param OrderRepositoryInterface $orderRepository
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionResource $subscriptionResource
     * @param SubscriptionOrderFactory $subscriptionOrderFactory
     * @param SubscriptionOrderItemFactory $subscriptionOrderItemFactory
     * @param SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory
     * @param SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory
     * @param RecurlyHelper $recurlyHelper
     * @param SubscriptionOrderResource $subscriptionOrderResource
     * @param ItemResource $itemResource
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory
     * @param ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory
     * @param ShipmentHelper $shipmentHelper
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param Config $config
     */
    public function __construct(
        LoggerInterface $logger,
        ResponseHelper $responseHelper,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        DiscountHelper $discountHelper,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        CustomerResource $customerResource,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        QuoteManagement $quoteManagement,
        QuoteFactory $quoteFactory,
        AddressRepositoryInterface $addressRepository,
        ProductFactory $productFactory,
        ProductResource $productResource,
        AdvancedFactory $advancedFactory,
        AdvancedResource $advancedResource,
        AddressFactory $addressFactory,
        ProductRepository $productRepository,
        CustomerRepositoryInterface $customerRepository,
        QuoteResource $quoteResource,
        OrderRepositoryInterface $orderRepository,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionResource $subscriptionResource,
        SubscriptionOrderFactory $subscriptionOrderFactory,
        SubscriptionOrderItemFactory $subscriptionOrderItemFactory,
        SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory,
        SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory,
        RecurlyHelper $recurlyHelper,
        SubscriptionOrderResource $subscriptionOrderResource,
        ItemResource $itemResource,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        ShipOrderInterface $shipOrderInterface,
        ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory,
        ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory,
        ShipmentHelper $shipmentHelper,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        Config $config
    ) {
        $this->_logger = $logger;
        $host = gethostname();
        $ip = gethostbyname($host);
        $this->_loggerPrefix = 'SERVER: ' . $ip . ' SESSION: ' . session_id() . ' - ';
        $this->_responseHelper = $responseHelper;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_discountHelper = $discountHelper;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_invoiceSender = $invoiceSender;
        $this->_customerResource = $customerResource;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteFactory = $quoteFactory;
        $this->_addressRepository = $addressRepository;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
        $this->_advancedFactory = $advancedFactory;
        $this->_advancedResource = $advancedResource;
        $this->_addressFactory = $addressFactory;
        $this->_productRepository = $productRepository;
        $this->_customerRepository = $customerRepository;
        $this->_quoteResource = $quoteResource;
        $this->_orderRepository = $orderRepository;
        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_subscriptionOrderFactory = $subscriptionOrderFactory;
        $this->_subscriptionOrderItemFactory = $subscriptionOrderItemFactory;
        $this->_subscriptionAddonOrderFactory = $subscriptionAddonOrderFactory;
        $this->_subscriptionAddonOrderItemFactory = $subscriptionAddonOrderItemFactory;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionOrderResource = $subscriptionOrderResource;
        $this->_itemResource = $itemResource;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_shipOrderInterface = $shipOrderInterface;
        $this->_shipmentItemCreationInterfaceFactory = $shipmentItemCreationInterfaceFactory;
        $this->_shipmentTrackCreationInterfaceFactory = $shipmentTrackCreationInterfaceFactory;
        $this->_shipmentHelper = $shipmentHelper;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->config = $config;
    }

    /**
     * Creates a new order.
     *
     * @param $requestData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createOrder($orderData)
    {
        // Ensure we are not given an empty request
        if (empty($orderData)) {
            return $this->_responseHelper->createResponse(false, "Request cannot be empty.");
        }

        // Log order request DTO
        $this->_logger->info('Processing order: ' . json_encode($orderData));

        // Ensure the data provided to us for order creation is accurate and complete.
        $isNotValidated = $this->validateOrderData($orderData);
        if ($isNotValidated) {
            return $isNotValidated;
        }

        // Get store and website information
        $store = $this->_storeManager->getStore($orderData["storeId"]);
        if (!$store->getId()) {
            return $this->_responseHelper->createResponse(false, "Store not found.");
        }

        // Create Quote object.
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->_quoteFactory->create();
        $quote->setStore($store);

        $quote->setCustomerFirstname($orderData['customerFirstName']  ?? null);
        $quote->setCustomerLastname($orderData['customerLastName'] ?? null);

        // Load and add each product to the quote.
        foreach ($orderData['products'] as $item) {

            // Get the Product.
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->_productRepository->get($item['sku']);

            // Override price if it exists on the json object
            $product->setPrice($item['price'] ?? $product->getPrice());

            // make sure that this product exists
            if (!$product) {
                return $this->_responseHelper->createResponse(false, 'Product with sku of ' . $item['sku'] . ' not found.');
            }
            // Add the product to our quote.
            $quote->addProduct($product, $item['qty']);
        }

        // Apply coupon code if available.
        $quote->setCouponCode($orderData["couponCode"] ?? $quote->getCouponCode());


        // Create Billing Address and associate it to the quote.
        /* @var $orderBillingAddress \Magento\Quote\Model\Quote\Address */
        $orderBillingAddress = $this->_addressFactory->create();
        $orderBillingAddress->setAddressType(\Magento\Sales\Model\Order\Address::TYPE_BILLING);
        $orderBillingAddress->setFirstname($orderData['billingAddress']['firstName']);
        $orderBillingAddress->setLastname($orderData['billingAddress']['lastName']);
        $orderBillingAddress->setStreet($orderData['billingAddress']['street1']);
        $orderBillingAddress->setCity($orderData['billingAddress']['city']);
        $orderBillingAddress->setRegion($orderData['billingAddress']['region']);
        $orderBillingAddress->setPostcode($orderData['billingAddress']['postalCode']);
        $orderBillingAddress->setCountryId($orderData['billingAddress']['country']);
        $orderBillingAddress->setTelephone($orderData['billingAddress']['phone']);
        $quote->setShippingAddress($orderBillingAddress);

        // Create Shipping Address and associate it to the quote.
        /* @var $orderShippingAddress \Magento\Quote\Model\Quote\Address */
        $orderShippingAddress = $this->_addressFactory->create();
        $orderShippingAddress->setAddressType(\Magento\Sales\Model\Order\Address::TYPE_SHIPPING);
        $orderShippingAddress->setFirstname($orderData['shippingAddress']['firstName']);
        $orderShippingAddress->setLastname($orderData['shippingAddress']['lastName']);
        $orderShippingAddress->setStreet($orderData['shippingAddress']['street1']);
        $orderShippingAddress->setCity($orderData['shippingAddress']['city']);
        $orderShippingAddress->setRegion($orderData['shippingAddress']['region']);
        $orderShippingAddress->setPostcode($orderData['shippingAddress']['postalCode']);
        $orderShippingAddress->setCountryId($orderData['shippingAddress']['country']);
        $orderShippingAddress->setTelephone($orderData['shippingAddress']['phone']);
        $orderShippingAddress->setCollectShippingRates(true);
        $orderShippingAddress->setShippingMethod('freeshipping_freeshipping');
        $quote->setShippingAddress($orderShippingAddress);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setPaymentMethod('recurly');
        $quote->setInventoryProcessed(false);
        $quote->setCustomerEmail($orderData['customerEmail']);
        $quote->setCustomerIsGuest(true);

        try {

            $this->_quoteResource->save($quote);

            // Set sales order payment.
            $quote->getPayment()->importData(['method' => 'recurly']);

            $quote->collectTotals();

            $this->_quoteResource->save($quote);

            // Generate the order from the quote.
            /** @var Order $order */
            $order = $this->_quoteManagement->submit($quote);
            $order->setEmailSent(0);

            // Set custom order fields

            $order->setData('ls_order_id', $orderData["lsOrderId"] ?? null);
            $order->setData('parent_order_id', $orderData["parentOrderId"] ?? null);

            // Set the Scotts Customer Id.
            $order->setData('scotts_customer_id', $orderData["customerId"] ?? null);

            // Overwrite M2 order pricing with pricing from core services if set.
            $order->setShippingAmount($orderData["shippingCost"] ?? $order->getShippingAmount());
            $order->setSubtotal($orderData["subTotal"] ?? $order->getSubtotal());
            $order->setDiscountAmount($orderData["discounts"] ?? $order->getDiscountAmount());
            $order->setTaxAmount($orderData["tax"] ?? $order->getTaxAmount());
            $order->setGrandTotal($orderData["total"] ?? $order->getGrandTotal());
            $order->setCouponCode($orderData["couponCode"] ?? $order->getCouponCode());

            // Save order
            $this->_orderResource->save($order);

            // Perform lawn subscription order specific processing.
            if ($orderData["orderType"] === "LS") {

                //create the master lawn subscription object
                /** @var Subscription $subscription */
                $subscription = $this->createSubscription($order, $orderData);

                // Set ship date for the subscription/order
                $order->addData([
                    'ship_start_date' => $orderData["shipStartDate"] ?? NULL,
                    'ship_end_date' => $orderData["shipEndDate"] ?? NULL,
                    'subscription_addon' => $orderData["isAddOn"] ?? FALSE,
                    'subscription_type' => $subscription->getSubscriptionType(),
                    'master_subscription_id' => $orderData['parentOrderId'],
                    'subscription_id' => $orderData['lsOrderId'],
                    'gigya_id' => $orderData['gigyaId'] ?? NULL
                ]);

                // Save subscription data to order.
                $this->_orderResource->save($order);

            }

            $response = array(
                'statusCode' => 200,
                'statusMessage' => 'success',
                'response' => $order->getId()
            );

            // Log order response DTO
            $this->_logger->info('Finished processing order: ' . json_encode($order->getData()));

            // return the order object.
            return $response;

        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());
        }
    }

    protected function validateOrderData($orderData)
    {
        if (empty($orderData["customerId"])) {
            return $this->_responseHelper->createResponse(false, "There must be a customer id.");
        }

        if (empty($orderData["customerFirstName"])) {
            return $this->_responseHelper->createResponse(false, "There must be a customer first name.");
        }

        if (empty($orderData["customerLastName"])) {
            return $this->_responseHelper->createResponse(false, "There must be a customer last name.");
        }

        if (empty($orderData["storeId"])) {
            return $this->_responseHelper->createResponse(false, "There must be a store id.");
        }

        if (empty($orderData["products"])) {
            return $this->_responseHelper->createResponse(false, "There must be products.");
        }
        if (empty($orderData["shippingAddress"])) {
            return $this->_responseHelper->createResponse(false, "There must be a shipping address.");
        }

        if (empty($orderData["shippingAddress"]['firstName'])) {
            return $this->_responseHelper->createResponse(false, "There must be a first name on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['lastName'])) {
            return $this->_responseHelper->createResponse(false, "There must be a last name on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['street1'])) {
            return $this->_responseHelper->createResponse(false, "There must be a street1 on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['city'])) {
            return $this->_responseHelper->createResponse(false, "There must be a city on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['region'])) {
            return $this->_responseHelper->createResponse(false, "There must be a region (state) on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['postalCode'])) {
            return $this->_responseHelper->createResponse(false, "There must be a postal code (zip) on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['country'])) {
            return $this->_responseHelper->createResponse(false, "There must be a country on the shipping address.");
        }

        if (empty($orderData["shippingAddress"]['phone'])) {
            return $this->_responseHelper->createResponse(false, "There must be a phone on the shipping address.");
        }

        if (empty($orderData["billingAddress"])) {
            return $this->_responseHelper->createResponse(false, "There must be a billing address.");
        }

        if (empty($orderData["billingAddress"]['firstName'])) {
            return $this->_responseHelper->createResponse(false, "There must be a first name on the billing address");
        }

        if (empty($orderData["billingAddress"]['lastName'])) {
            return $this->_responseHelper->createResponse(false, "There must be a last name on the billing address");
        }

        if (empty($orderData["billingAddress"]['street1'])) {
            return $this->_responseHelper->createResponse(false, "There must be a street1 on the billing address.");
        }

        if (empty($orderData["billingAddress"]['city'])) {
            return $this->_responseHelper->createResponse(false, "There must be a city on the billing address.");
        }

        if (empty($orderData["billingAddress"]['region'])) {
            return $this->_responseHelper->createResponse(false, "There must be a region (state) on the billing address.");
        }

        if (empty($orderData["billingAddress"]['postalCode'])) {
            return $this->_responseHelper->createResponse(false, "There must be a postal code (zip) on the billing address.");
        }

        if (empty($orderData["billingAddress"]['country'])) {
            return $this->_responseHelper->createResponse(false, "There must be a country on the billing address.");
        }

        if (empty($orderData["billingAddress"]['phone'])) {
            return $this->_responseHelper->createResponse(false, "There must be a phone on the billing address.");
        }

        if (empty($orderData['orderType'])) {
            return $this->_responseHelper->createResponse(false, "There must be an order type.");
        }


        return false;
    }

    /**
     * Creates a master subscription
     *
     * @param Order $order
     * @param mixed $orderData
     * @return Subscription
     * @throws \Magento\Framework\Exception\AlreadyExistsException|\Magento\Framework\Exception\LocalizedException
     *
     */
    protected function createSubscription($order, $orderData)
    {
        // Load subscription from parent order
        /** @var Subscription $subscription */
        $subscription = $this->_subscriptionResource
            ->getSubscriptionByMasterSubscriptionId($orderData['parentOrderId']);

        if (!$subscription) {
            // Create the master subscription
            /** @var Subscription $subscription */
            $subscription = $this->_subscriptionFactory->create();
            $subscription->setData('gigya_id', $order->getData("customer_gigya_id") ?? null);
            $subscription->setData('quiz_id', $orderData['completedQuizId']);
            $subscription->setData('lawn_zip', $orderData['lawnZip']);
            $subscription->setData('zone_name', $orderData['lawnZone']);
            $subscription->setData('subscription_type', $orderData['subType']);
            $subscription->setData('origin', $orderData['cartType']);
            $subscription->setData('subscription_status', $orderData['subStatus'] ?? "active");
            $subscription->setData('tax', $order->getTaxAmount());
            $subscription->setData('paid', $orderData['paid']);
            $subscription->setData('price', $orderData['price']);
            $subscription->setData('discount', $order->getDiscountAmount());
            $subscription->setData('subscription_id', $orderData['parentOrderId']);
            $this->_subscriptionResource->save($subscription);
        } else {
            $subscription->setData('paid', strval(floatval($orderData['paid']) + floatval($subscription->getData('paid'))));
            $subscription->setData('price', strval(floatval($orderData['price']) + floatval($subscription->getData('price'))));
            $subscription->setData('discount', strval(floatval($order->getDiscountAmount()) + floatval($subscription->getData('discount'))));
            $subscription->setData('tax', strval(floatval($order->getTaxAmount()) + floatval($subscription->getData('tax'))));
            $this->_subscriptionResource->save($subscription);
        }
        // Only get first product
        foreach ($orderData['products'] as $item) {
            $this->createSubscriptionOrder($subscription, $orderData, $item, $order->getId());
            break;
        }

        return $subscription;
    }

    /**
     * Creates a master subscription
     *
     * @param Subscription $masterSubscription
     * @param mixed $orderData
     * @param mixed $product
     * @param string $orderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function createSubscriptionOrder($masterSubscription, $orderData, $product, $orderId)
    {
        /** @var SubscriptionOrderResource $subscriptionOrder */
        $subscriptionOrder = $this->_subscriptionOrderFactory->create();
        $subscriptionOrder->setData('subscription_entity_id', $masterSubscription->getId());
        $subscriptionOrder->setData('sales_order_id', $orderId);
        $subscriptionOrder->setData('price', $orderData['price'] ?? 0.00);
        $subscriptionOrder->setData('application_start_date', $product['applicationWindow']['startDate']);
        $subscriptionOrder->setData('application_end_date', $product['applicationWindow']['endDate']);
        $subscriptionOrder->setData('ship_start_date', $orderData["shipStartDate"]);
        $subscriptionOrder->setData('ship_end_date', $orderData["shipEndDate"]);
        $subscriptionOrder->setData('subscription_order_status', $orderData['subStatus'] ?? "pending");
        $subscriptionOrder->setData('season_name', $product['applicationWindow']['season']);
        $subscriptionOrder->setData('season_slug', $this->_recurlyHelper->getSeasonSlugByName($product['applicationWindow']['season']));
        $subscriptionOrder->setData('subscription_id', $orderData['lsOrderId']);

        $this->_subscriptionOrderResource->save($subscriptionOrder);
    }

    /**
     * Gets an existing order.
     *
     * @param $requestData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getOrder($orderData)
    {

        // Log order request DTO
        $this->_logger->info('Processing method getOrder with orderId: ' . json_encode($orderData));

        /** @var Order $order */
        $order = $this->_orderRepository->get($orderData['orderId']);
        $orderObject = $order->getData();
        $payment = $order->getPayment();
        $methodname = $payment->getMethod();
        $additionalInfo = ($payment->getAdditionalInformation());

       if(isset($additionalInfo['last_four'])){

        $last4 = $additionalInfo['last_four'];
        $cc_type = $this->config->getCardFullName($payment->getData('cc_type'));

    }else

    {
        $last4= "";
        $cc_type= "";
    }
        $orderObject['hdr_disc_fixed_amount'] = '';
        $orderObject['hdr_disc_perc'] = '';
        $orderObject['hdr_disc_cond_code'] = '';

        if(!empty($order->getData('coupon_code'))){
            $orderDiscount = $this->_discountHelper->DiscountCode($order->getData('coupon_code'));
            $orderObject['hdr_disc_fixed_amount'] = $orderDiscount['hdr_disc_fixed_amount'];
            $orderObject['hdr_disc_perc'] = $orderDiscount['hdr_disc_perc'];
            $orderObject['hdr_disc_cond_code'] = $orderDiscount['hdr_disc_cond_code'];
        }

        $orderObject['billingAddress'] = $order->getBillingAddress()->getData();
        $orderObject['shippingAddress'] = $order->getShippingAddress()->getData();

        $orderObject['products'] = [];

        // Populate the products array
        /** @var Product $item */
        foreach ($order->getAllItems() as $item) {
            $product = $item->getData();

            // If configurable, get parent price
            $price = $item->getOriginalPrice();

            if (!empty($item->getParentItemId()))
            {
                $parent = $this->_orderItemCollectionFactory->create()->addFieldToFilter('item_id', ['eq' => $item->getParentItemId()]);

                /**
                * There will be only one result since we filter on the unique id
                *
                * @var \Magento\Sales\Model\Order\Item $parentItem
                */
                    $parentItem = $parent->getFirstItem();
                if ($parentItem->getProductType() === "configurable")
                {
                    $price = $parentItem->getOriginalPrice();
                }
            }

            $parent_p['parent_price']= $price;
            $product = array_merge($product,$parent_p);
            $orderObject['products'][] = $product;

        }

        // Populate the subscription.
        /** @var Subscription $subscription */
        $subscriptionOrder = $this->_subscriptionOrderFactory->create();
        $this->_subscriptionOrderResource->load($subscriptionOrder, $order->getId(), 'sales_order_id');

        $subscription = $this->_subscriptionFactory->create();
        $this->_subscriptionResource->load($subscription, $subscriptionOrder->getData('subscription_entity_id'), 'entity_id');

        $orderObject['masterSubscription'] = $subscription->getData();
        $orderObject['subscriptions'] = $subscriptionOrder->getData();

        $orderObject['shipments'] = $order->getShipmentsCollection()->getData();

        // get the shipping condition data
        /** @var /SMG/OfflineShipping/Model/ShippingConditionCode $shippingCondition */
        $shippingCondition = $this->_shippingConditionCodeFactory->create();
        $this->_shippingConditionCodeResource->load($shippingCondition, $order->getShippingMethod(), 'shipping_method');

        $orderObject['shippingCondition'] = $shippingCondition->getData();
        
        $orderObject['last_four'] = $last4;
        $orderObject['cc_type'] = $cc_type;
        
        $response = array(
            'statusCode' => 200,
            'statusMessage' => 'success',
            'response' => $orderObject
        );

        // Log getOrder response DTO
        $this->_logger->info('Finished retrieving order: ' . json_encode($response));

        // return the order object.
        return $response;
    }

    /**
     * Updates the subscription status of an order.
     *
     * @param $requestData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updateOrderSubscriptionStatus($requestData)
    {

        $this->_logger->info('Processing method updateOrderSubscriptionStatus request: ' . json_encode($requestData));

        // Ensure there is a subscription id.
        if (empty($requestData['subscriptionId'])) {
            return $this->_responseHelper->createResponse(false, "There must be a subscription id.");
        }

        // Ensure there is a subscription status.
        if (empty($requestData['status'])) {
            return $this->_responseHelper->createResponse(false, "There must be a subscription status.");
        }

        // Load the subscription.
        /** @var SubscriptionOrder $subscriptionOrder */
        $subscriptionOrder = $this->_subscriptionOrderFactory->create();
        $this->_subscriptionOrderResource->load($subscriptionOrder, $requestData['subscriptionId'], 'entity_id');

        // Update the subscription status.
        $subscriptionOrder->setData('subscription_order_status', $requestData['status']);
        $this->_subscriptionOrderResource->save($subscriptionOrder);

        // Return a successful response.
        $response = array(
            'statusCode' => 200,
            'statusMessage' => 'success',
            'response' => $subscriptionOrder->getData()
        );

        // Log updateOrderSubscriptionStatus response DTO
        $this->_logger->info('Finished updating order subscriptionstatus: ' . json_encode($response));

        return $response;
    }

    /**
     * Gets an array of products by their sku(s).
     *
     * @param $requestData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getProducts($requestData)
    {

        $this->_logger->info('Processing method getProducts request: ' . json_encode($requestData));

        $skus = explode(',', $requestData['skus']);
        $products = [];
        // Load and add each product to the quote.
        foreach ($skus as $sku) {

            // Get the Product by sku
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->_productRepository->get($sku);

            // make sure that this product exists
            if (!$product) {
                $this->_logger->info("Could not find product with sku: ${$sku}");
                continue;
            }

            // Add the product to our return object.
            // TODO: possibly remove unneeded fields.
            $productData = $product->getData();

            $productData['status'] = $product->getAttributeText('status') ? $product->getAttributeText('status')->getText() : '';
            $productData['size'] = $product->getAttributeText('size') ? (array)$product->getAttributeText('size') : [];
            $productData['visibility'] = $product->getAttributeText('visibility') ? $product->getAttributeText('visibility')->getText() : '';
            $productData['country_of_manufacture'] = $product->getAttributeText('country_of_manufacture') ? (array)$product->getAttributeText('country_of_manufacture') : [];
            $productData['bundle'] = $product->getAttributeText('bundle') ? (array)$product->getAttributeText('bundle') : [];
            $productData['bundle_type'] = $product->getAttributeText('bundle_type') ? (array)$product->getAttributeText('bundle_type') : [];
            $productData['company'] = $product->getAttributeText('company') ? (array)$product->getAttributeText('company') : [];
            $productData['lawn_zone_available_in'] = $product->getAttributeText('lawn_zone_available_in') ? (array)$product->getAttributeText('lawn_zone_available_in') : [];
            $productData['lawn_zone_excluded'] = $product->getAttributeText('lawn_zone_excluded') ? (array)$product->getAttributeText('lawn_zone_excluded') : [];
            $productData['mylawn_lawn_condition'] = $product->getAttributeText('mylawn_lawn_condition') ? (array)$product->getAttributeText('mylawn_lawn_condition') : [];
            $productData['sap_base_unit'] = $product->getAttributeText('sap_base_unit') ? (array)$product->getAttributeText('sap_base_unit') : [];
            $productData['sap_brand'] = $product->getAttributeText('sap_brand') ? (array)$product->getAttributeText('sap_brand') : [];
            $productData['sap_division'] = $product->getAttributeText('sap_division') ? (array)$product->getAttributeText('sap_division') : [];
            $productData['sap_ean_category'] = $product->getAttributeText('sap_ean_category') ? (array)$product->getAttributeText('sap_ean_category') : [];
            $productData['sap_ean_category_each'] = $product->getAttributeText('sap_ean_category_each') ? (array)$product->getAttributeText('sap_ean_category_each') : [];
            $productData['sap_lwh_unit'] = $product->getAttributeText('sap_lwh_unit') ? (array)$product->getAttributeText('sap_lwh_unit') : [];
            $productData['sap_lwh_unit_each'] = $product->getAttributeText('sap_lwh_unit_each') ? (array)$product->getAttributeText('sap_lwh_unit_each') : [];
            $productData['sap_material_group'] = $product->getAttributeText('sap_material_group') ? (array)$product->getAttributeText('sap_material_group') : [];
            $productData['sap_material_group_pkg_materials'] = $product->getAttributeText('sap_material_group_pkg_materials') ? (array)$product->getAttributeText('sap_material_group_pkg_materials') : [];
            $productData['sap_material_status'] = $product->getAttributeText('sap_material_status') ? (array)$product->getAttributeText('sap_material_status') : [];
            $productData['sap_material_type'] = $product->getAttributeText('sap_material_type') ? (array)$product->getAttributeText('sap_material_type') : [];
            $productData['sap_subbrand'] = $product->getAttributeText('sap_subbrand') ? (array)$product->getAttributeText('sap_subbrand') : [];
            $productData['sap_volume_unit'] = $product->getAttributeText('sap_volume_unit') ? (array)$product->getAttributeText('sap_volume_unit') : [];
            $productData['sap_volume_unit_each'] = $product->getAttributeText('sap_volume_unit_each') ? (array)$product->getAttributeText('sap_volume_unit_each') : [];
            $productData['sap_weight_unit'] = $product->getAttributeText('sap_weight_unit') ? (array)$product->getAttributeText('sap_weight_unit') : [];
            $productData['sap_weight_unit_each'] = $product->getAttributeText('sap_weight_unit_each') ? (array)$product->getAttributeText('sap_weight_unit_each') : [];
            $productData['goals_filter'] = $product->getAttributeText('goals_filter') ? (array)$product->getAttributeText('goals_filter') : [];
            $productData['mylawn_grass_types'] = $product->getAttributeText('mylawn_grass_types') ? (array)$product->getAttributeText('mylawn_grass_types') : [];
            $productData['mylawn_sunlight'] = $product->getAttributeText('mylawn_sunlight') ? (array)$product->getAttributeText('mylawn_sunlight') : [];
            $productData['mylawn_weed_type'] = $product->getAttributeText('mylawn_weed_type') ? (array)$product->getAttributeText('mylawn_weed_type') : [];
            $productData['problems_filter'] = $product->getAttributeText('problems_filter') ? (array)$product->getAttributeText('problems_filter') : [];
            $productData['product_websites'] = $product->getAttributeText('product_websites') ? (array)$product->getAttributeText('product_websites') : [];
            $productData['states_available_in'] = $product->getAttributeText('states_available_in') ? (array)$product->getAttributeText('states_available_in') : [];
            $productData['mylawn_lawn_zone'] = $product->getAttributeText('mylawn_lawn_zone') ? (array)$product->getAttributeText('mylawn_lawn_zone') : [];
            $productData['sync_with_my_lawn_app'] = $product->getAttributeText('sync_with_my_lawn_app') ? (array)$product->getAttributeText('sync_with_my_lawn_app') : [];
            $productData['mylawn_categories'] = $product->getAttributeText('mylawn_categories') ? (array)$product->getAttributeText('mylawn_categories') : [];

            $attribute = $product->getData('state_not_allowed');
            $states = array();

            if ($attribute) {
                forEach (explode(',', $attribute) as $id) {
                    $states[] = $product->getAttributes()['state_not_allowed']->getSource()->getOptionText($id);
                }
            }

            $productData['state_not_allowed'] = $states;


            /**
             * COM-962 - Build full thumbnail image url
             */
            $thumbnailUrl = self::IMAGE_URL . ($productData['thumbnail'] ?? '');
            $productData['thumbnail'] = $thumbnailUrl;

            $smallImageUrl = self::IMAGE_URL . ($productData['small_image'] ?? '');
            $productData['small_image'] = $smallImageUrl;

            $imageUrl = self::IMAGE_URL . ($productData['image'] ?? '');
            $productData['image'] = $imageUrl;

            $products[] = $productData;
        }


        // Return a successful response.
        $response = array(
            'statusCode' => 200,
            'statusMessage' => 'success',
            'response' => $products
        );

        // Log getProducts response DTO
        $this->_logger->info('Finished retrieving products: ' . json_encode($response));

        return $response;
    }

    /**
     * Create the Shipment Request to set the order as
     * shipped.
     *
     * @param $orderData
     * @return array
     */
    public function createShipment($orderData) {
        $orderId = $orderData['order_id'];
        //Check if order exists
        try {
            /** @var Order $order */
            $order = $this->_orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            return array(
                'statusCode' => 404,
                'statusMessage' => 'failure',
                'response' => 'Order not found with id: ' . $orderId
            );
        } catch (InputException $e) {
            return array(
                'statusCode' => 400,
                'statusMessage' => 'failure',
                'response' => 'No order id provided.'
            );
        }

        // determine if this can be shipped
        if (!$this->_shipmentHelper->canShip($order)) {
            $this->_logger->error("The order Id " . $orderId . " can not be shipped.  The order status currently is " . $order->getStatus());
            return array(
                'statusCode' => 403,
                'statusMessage' => 'failure',
                'response' => 'The Magento order cannot ship.'
            );
        }

        $items = [];
        $tracks = [];
        $hasError = false;
        $response = array(
            'statusCode' => 500,
            'statusMessage' => 'failure',
            'response' => 'Unknown Error.'
        );

        /**
         * @var array $shippedItems
         */
        $shippedItems = $orderData['shipped'];
        $shippingTitle = "Federal Express - " . $order->getShippingDescription();

        // Create Shipments and Tracking Numbers
        try {
            foreach ($shippedItems as $shipped) {
                // Do not create the tracking number record if it already exists on the Magento 2 order.
                $trackingNumberExists = false;
                $trackingNumber = $shipped['tracking_number'];
                $sku = $shipped['sku'];
                $quantity = $shipped['qty_shipped'];

                foreach ($order->getTracksCollection() as $track) {
                    if ($track->getTrackNumber() === $trackingNumber) {
                        $trackingNumberExists = true;
                    }
                }

                if (!$trackingNumberExists) {
                    /**
                     * @var \Magento\Sales\Api\Data\ShipmentTrackCreationInterface @$shipmentTrackItemCreation
                     */
                    $shipmentTrackItemCreation = $this->_shipmentTrackCreationInterfaceFactory->create();
                    $shipmentTrackItemCreation->setTrackNumber($trackingNumber);
                    $shipmentTrackItemCreation->setTitle($shippingTitle);
                    $shipmentTrackItemCreation->setCarrierCode("fedex");
                    $tracks[] = $shipmentTrackItemCreation;
                }

                // get the magento order item for the current sap order item.
                /**
                 * @var \Magento\Sales\Model\Order\Item $orderItem
                 */
                $orderItem = array_values(array_filter($order->getAllItems(), function ($item) use (&$sku) {
                    return $item->getData('sku') == $sku;
                }));

                if (empty($orderItem)) {
                    $this->_logger->error('Could not find sku: ' . $sku . ' in order: ' . $orderId);
                    return array(
                        'statusCode' => 404,
                        'statusMessage' => 'failure',
                        'response' => 'Could not find sku: ' . $sku . ' in order: ' . $orderId
                    );
                }
                // Do not add the item to the track if its quantity is 0.
                if (floatval($quantity) < 1) {
                    continue;
                }
                // Do not add the item to the track if all items have shipped.
                if (floatval($orderItem[0]->getData('qty_shipped')) == floatval($orderItem[0]->getData('qty_ordered'))) {
                    $this->_logger->error("Item already shipped: orderItem.item_id=" . $orderItem[0]->getItemId());
                    return array(
                        'statusCode' => 400,
                        'statusMessage' => 'failure',
                        'response' => 'Sku already fully shipped: ' . $sku . ' in order: ' . $orderId
                    );
                }
                /**
                 * @var \Magento\Sales\Api\Data\ShipmentItemCreationInterface $shipmentItemCreation
                 */
                $shipmentItemCreation = $this->_shipmentItemCreationInterfaceFactory->create();
                $shipmentItemCreation->setOrderItemId($orderItem[0]->getItemId());
                $shipmentItemCreation->setQty($quantity);
                $items[] = $shipmentItemCreation;
            }
        } catch (Exception $e) {
            $this->_logger->error('Error creating shipments for order: ' . $orderId);
            return array(
                'statusCode' => 500,
                'statusMessage' => 'failure',
                'response' => 'Internal error creating shipments for order.',
                'stack' => $e->getTraceAsString()
            );
        }

        // check to see if the items were added
        if (!empty($items) && !empty($tracks)) {

            try {
                    // Load the subscription.
                    /** @var SubscriptionOrder $subscriptionOrder */
                $subscriptionOrder = $this->_subscriptionOrderFactory->create();
                $this->_subscriptionOrderResource->load($subscriptionOrder, $order->getData('entity_id'), 'sales_order_id');

                // Update the subscription status.
                $subscriptionOrder->setData('subscription_order_status', $orderData['new_status']);
                $this->_subscriptionOrderResource->save($subscriptionOrder);
            } catch (Exception $e) {
                return array(
                    'statusCode' => 500,
                    'statusMessage' => 'failure',
                    'response' => 'Internal error saving subscription order details for order.',
                    'stack' => $e->getTraceAsString()
                );
            }

            try {
                // create shipment status
                $shipmentId = $this->_shipOrderInterface->execute($orderId, $items, false, false, null, $tracks);
            } catch (Exception $e) {
                return array(
                    'statusCode' => 500,
                    'statusMessage' => 'failure',
                    'response' => 'Internal error saving shipment on order.',
                    'stack' => $e->getTraceAsString()
                );
            }

            // Return a successful response.
            return array(
                'statusCode' => 200,
                'statusMessage' => 'success',
                'response' => $shipmentId
            );
        } else {
            $this->_logger->error("The order Id " . $orderId . " found nothing applicable to process from request.");
            return array(
                'statusCode' => 404,
                'statusMessage' => 'failure',
                'response' => 'Nothing was found to process.'
            );
        }
    }

}
