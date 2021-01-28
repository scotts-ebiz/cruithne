<?php

namespace SMG\SubscriptionApi\Helper;

use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem\CollectionFactory as SubscriptionOrderItemCollectionFactory;
use SMG\SubscriptionApi\Model\Subscription;
use Zaius\Engage\Helper\Sdk as ZaiusSdk;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use ZaiusSDK\ZaiusException;
use SMG\BackendService\Model\Service\Order as OrderBackendService;
use Magento\Backend\Model\Auth\Session as AuthSession;

class CancelHelper extends AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var ZaiusSdk
     */
    protected $_sdk;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var CustomerCollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /** @var SubscriptionOrderCollectionFactory */
    protected $_subscriptionOrderCollectionFactory;

    /** @var SubscriptionOrderItemCollectionFactory */
    protected $_subscriptionOrderItemCollectionFactory;

    /** @var productRepository */
    protected $_productRepository;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var string
     */
    protected $_loggerPrefix;
    
     /**
     * @var OrderBackendService
     */
    protected $_orderService;

    /**
     * @var AuthSession
     */
    protected $_adminSession;

    /**
     * CancelHelper constructor.
     * @param Context $context
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CustomerSession $customerSession
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param LoggerInterface $logger
     * @param ZaiusSdk $sdk
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory
     * @param ProductRepository $productRepository
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param OrderBackendService $orderService
     * @param  AuthSession $_adminSession
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerSession $customerSession,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        LoggerInterface $logger,
        ZaiusSdk $sdk,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory,
        ProductRepository $productRepository,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        OrderBackendService $orderService,
        AuthSession $_adminSession
    ) {
        parent::__construct($context);

        $this->_addressRepository = $addressRepository;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_logger = $logger;
        $this->_sdk = $sdk;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionOrderItemCollectionFactory = $subscriptionOrderItemCollectionFactory;
        $this->_productRepository = $productRepository;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_orderService = $orderService;
        $this->_adminSession = $_adminSession;

        $host = gethostname();
        $ip = gethostbyname($host);
        $this->_loggerPrefix = 'SERVER: ' . $ip . ' SESSION: ' . session_id() . ' - ';
    }

    /**
     * @param string $accountCode
     * @return false|string
     * @throws LocalizedException
     */
    public function cancelSubscriptions($accountCode = '', $cancelReason = '', $area = '')
    {
        // Get the current user.
        try {
            if (! $accountCode) {
                $accountCode = $this->_customerSession->getCustomer()->getData('gigya_uid');
            }

            $this->_logger->info($this->_loggerPrefix . "Cancelling subscription for account with Gigya ID: {$accountCode}");

            /**
             * @var $subscription Subscription
             */
            if($area == 'admin' || $area == 'api'){
                
            $subscription = $this->_subscriptionCollectionFactory
                ->create()
                ->addFieldToFilter('subscription_id', $accountCode)
                ->addFieldToFilter('subscription_status', 'active')
                ->fetchItem();
                
            }
            else
            {
                $subscription = $this->_subscriptionCollectionFactory
                ->create()
                ->addFieldToFilter('gigya_id', $accountCode)
                ->addFieldToFilter('subscription_status', 'active')
                ->fetchItem();
            }
            
            if (! $subscription || ! $subscription->getId()) {
                // Could not find the subscription.
                $error = 'Could not find an active subscription with Gigya user ID "' . $accountCode . '" to cancel.';
                $this->_logger->error($this->_loggerPrefix . $error);

                throw new Exception($error);
            }

            // Need to use collection here, the customer resource overrides the
            // normal load method and requires an ID instead of being able to
            // provide a field.
           
            //stop cancel subscription from backend
            if($area != 'admin' && $area != 'api'){
                
            $customer = $this->_customerCollectionFactory
                ->create()
                ->addFieldToFilter('gigya_uid', $accountCode)
                ->fetchItem();

            if (! $customer->getId()) {
                throw new Exception("Could not find customer with Gigya ID: {$accountCode}.");
            }

             $subscription->cancel();
            }
            else if ($area == 'api')
            {
                $customer = $this->_customerCollectionFactory
                ->create()
                ->addFieldToFilter('gigya_uid', $subscription->getGigyaId())
                ->fetchItem();

                if (! $customer->getId()) {
                    throw new Exception("Could not find customer with Gigya ID: {$subscription->getGigyaId()}.");
                }

                $subscription->cancel();
            }               
            // Add cancellation comments to orders.
            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders
                ->addFieldToFilter('subscription_entity_id', $subscription->getId());
            foreach ($subscriptionOrders as $subscriptionOrder) {
                $orderId = $subscriptionOrder->getSalesOrderId();
                $order = $this->_orderFactory->create();
                $this->_orderResource->load($order, $orderId);
                
                //trigger api to backend service team
                
                if($area == 'admin') {
                    $this->_orderService->cancelOrderSubcription($order->getIncrementId(), $order->getState());
                    $order->addCommentToStatusHistory('Subscription canceled by ' . $this->_adminSession->getUser()->getUserName(), false, false)
                        ->save();
                }
                else {
                    if ($cancelReason) {
                        $order->addCommentToStatusHistory('Subscription canceled by customer. Reason: ' . $cancelReason, false, false)
                            ->save();
                    }
                }
            }

            $timestamp = strtotime(date("Y-m-d H:i:s"));
            
            if($area != 'admin'){
                 
                $this->clearCustomerAddresses($customer);

                try {
                    $this->zaiusCancelCall($customer->getData('email'));
                    $this->zaiusCancelOrder($subscription, $customer->getData('email'), $timestamp);
                } catch (Exception $e) {
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());
                }
                
            }
        
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());

            throw new LocalizedException(__('There was an error while cancelling the subscription.'));
        }
    }

    /**
     * Delete customer addresses, because we don't want to store them in the address book,
     * so they will always need to enter their shipping/billing details on checkout
     *
     * @param $customer
     */
    private function clearCustomerAddresses($customer)
    {
        try {
            $customer->setDefaultBilling(null);
            $customer->setDefaultShipping(null);

            foreach ($customer->getAddresses() as $address) {
                $this->_addressRepository->deleteById($address->getId());
            }

            $customer->cleanAllAddresses();
            $customer->save();
        } catch (Exception $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        }
    }

    /**
     * Cancel subscription from zaius
     * @param $customer_email
     */
    private function zaiusCancelCall($customer_email)
    {
        $zaiusstatus = false;

        // check isSubcription and shipmentstatus
        if ($customer_email) {
            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();
            // take event as a array and add parameters
            $event = [];
            $event['type'] = 'subscription';
            $event['action'] = 'cancelled';
            $event['identifiers'] = ['email'=>$customer_email,'magento_store_view'=>'Default Store View'];

            // get postevent function
            try {
                $zaiusstatus = $zaiusClient->postEvent($event);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
                $this->_logger->error('A post to Zaius failed during cancellation, however, it should not affect the cancelled status.');
            }

            // check return values from the postevent function
            if (isset($zaiusstatus)) {
                $this->_logger->debug("The customer Email Subscription " . $customer_email . " is cancelled successfully to zaius."); //saved in var/log/debug.log
            } else {
                $this->_logger->error("The customer Email Subscription " . $customer_email . " is failed to zaius.");
            }
        }
    }

    /**
     * Cancel subscription notification to zaius
     * @param $subscription, $customeremail, $timestamp
     * return message on log
     */
    private function zaiusCancelOrder($subscription, $customer_email, $timestamp)
    {
        $zaiusstatus = false;

        // check isSubcription and shipmentstatus
        if ($subscription) {
            $subscriptionId = $subscription->getEntityId();

            // call subscription order
            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders
                ->setOrder('ship_start_date', 'asc')
                ->addFieldToFilter('subscription_entity_id', $subscriptionId);
            $this->_subscriptionOrders = $subscriptionOrders;

            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();
            // take event as a array and add parameters
            $event = [];

            $event['type'] = 'order';
            $event['action'] = 'cancel';
            $event['identifiers'] = ['email'=>$customer_email];
            foreach ($this->_subscriptionOrders as $orders) {
                $items = [];
                $order_id = $orders->getSalesOrderId();
                $total = $orders->getPrice();
                $orderItemId = $this->getOrderItems($orders->getEntityId());

                foreach ($orderItemId as $item) {
                    $product = $this->getProductBySku($item->getCatalogProductSku());
                    $items['product_id'] = $product->getEntityId();
                    $items['price'] = $item->getPrice();
                    $items['quantity'] = $item->getQty();
                    $items['subtotal'] = $items['price'] * $items['quantity'];
                }
                $order = ['order_id'=>$order_id,'total'=>$total,'items'=>[$items]];
                $event['data'] = ['ts'=>$timestamp,'magento_store_view'=>'Default Store View','order'=>$order];

                // get postevent function
                try {
                    $zaiusstatus = $zaiusClient->postEvent($event);
                } catch (Exception $e) {
                    $this->_logger->error($e->getMessage());
                    $this->_logger->error('A post to Zaius failed during cancellation, however, it should not affect the cancelled status.');
                }

                // check return values from the postevent function
                if ($zaiusstatus) {
                    $this->_logger->debug("The cancel order id" . $order_id . " is cancelled successfully to zaius."); //saved in var/log/debug.log
                } else {
                    $this->_logger->error("The cancel order id" . $order_id . " is failed to zaius.");
                }
            }
        }
    }

    public function getOrderItems($entity_id)
    {
        // Make sure we have an actual subscription
        if (empty($entity_id)) {
            return false;
        }
        // If subscription orders is local, send them, if not, pull them and send them
        $subscriptionOrderItems = $this->_subscriptionOrderItemCollectionFactory->create();
        $subscriptionOrderItems->addFieldToFilter('subscription_order_entity_id', $entity_id);
        $this->_subscriptionOrderItems = $subscriptionOrderItems;

        return $this->_subscriptionOrderItems;
    }

    public function getProductBySku($sku)
    {
        return $this->_productRepository->get($sku);
    }
}