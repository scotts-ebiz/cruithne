<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/9/19
 * Time: 3:55 PM
 */

namespace SMG\Sales\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\ProductOption;

use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\SubscriptionApi\Model\SubscriptionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\BackendService\Model\Service\Order as OrderBackendService;

class Order extends MagentoOrder
{
    /**
     * @var DiscountHelper
     */
    protected $_discountHelper;

    /**
     * @var SubscriptionFactory
     */
    protected $_subscriptionFactory;

    /**
     * @var SubscriptionResource
     */
    protected $_subscriptionResource;
    
    /**
     * @var OrderBackendService
     */
    protected $_orderService;

    public function __construct(\Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $addressCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $memoCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory,
        DiscountHelper $discountHelper,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionResource $subscriptionResource,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        ResolverInterface $localeResolver = null,
        ProductOption $productOption = null,
        OrderItemRepositoryInterface $itemRepository = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        OrderBackendService $orderService)
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $timezone, $storeManager, $orderConfig, $productRepository, $orderItemCollectionFactory, $productVisibility, $invoiceManagement, $currencyFactory, $eavConfig,
            $orderHistoryFactory, $addressCollectionFactory, $paymentCollectionFactory, $historyCollectionFactory, $invoiceCollectionFactory, $shipmentCollectionFactory, $memoCollectionFactory, $trackCollectionFactory,
            $salesOrderCollectionFactory, $priceCurrency, $productListFactory, $resource, $resourceCollection, $data, $localeResolver, $productOption, $itemRepository, $searchCriteriaBuilder
        );

        $this->_discountHelper = $discountHelper;
        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_orderService = $orderService;
    }

    /**
     * This will determine if the order is a subscription or
     * a regular order
     *
     * @return bool
     */
    public function isSubscription()
    {
        // variables
        $returnValue = false;

        // if there is a subscription type then it is a subscription and a master subscription id then
        // it is a subscription
        if (!empty($this->getSubscriptionType()) && !empty($this->getData('master_subscription_id')))
        {
            $returnValue = true;
        }

        // return whether this was a subscription or not
        return $returnValue;
    }

    /**
     * This function was needed because the data in sales_order for subscription
     * wasn't always valid we needed to do a check and get it from the subscription
     * table instead
     *
     * @return mixed
     */
    public function getSubscriptionType()
    {
        // variables
        $returnValue = $this->getData('subscription_type');
        if (empty($returnValue))
        {
            // load the subscription
            /**
             * @var \SMG\SubscriptionApi\Model\Subscription $subscription
             */
            $subscription = $this->_subscriptionFactory->create();
            $this->_subscriptionResource->load($subscription, $this->getData('master_subscription_id'), 'subscription_id');

            // check to see if there was a subscription
            if (!empty($subscription))
            {
                $returnValue = $subscription->getData('subscription_type');
            }
        }

        // return
        return $returnValue;
    }

    /**
     * This function will get the subscription order id based
     * on the subscription type
     *
     * @return mixed
     */
    public function getSubscriptionOrderId()
    {
        // get the subscription id for annuals
        // this will be the default return
        $subscriptionOrderId = $this->getData('master_subscription_id');
        if ($this->getData('subscription_type') == 'seasonal')
        {
            $subscriptionOrderId = $this->getData('subscription_id');
        }

        // return the appropriate subscription id
        return $subscriptionOrderId;
    }

    /**
     * Get the list of fields with the total amount for subscriptions
     *
     * @param array $listOfTotalFields
     * @return array
     */
    public function getAllSubscriptionTotals(array $listOfTotalFields)
    {
        // return array initialization
        $returnOrderTotals = array();

        // make sure that there were fields passed in to get the product values
        if (count($listOfTotalFields) > 0)
        {
            // get the subscription id
            $subscriptionOrderId = $this->getSubscriptionOrderId();

            // get the subscription type for later use
            $subscriptionType = $this->getData('subscription_type');

            // get the list of orders
            $magentoOrders = $this->salesOrderCollectionFactory->create();

            // get a list of orders that have the subscription id
            if ($subscriptionType == 'seasonal')
            {
                // filter out for those with the seasonal subscription id
                $magentoOrders->addFieldToFilter('subscription_id', ['eq' => $subscriptionOrderId]);
            } else
            {
                // filter out for those with the annual subscription id
                $magentoOrders->addFieldToFilter('master_subscription_id', ['eq' => $subscriptionOrderId]);
            }

            // loop through the list of orders
            /**
             * @var \Magento\Sales\Model\Order $magentoOrder
             */
            foreach($magentoOrders as $magentoOrder)
            {
                // loop through the desired fields
                foreach ($listOfTotalFields as $totalField)
                {
                    // make sure that there was a value entered
                    if (!empty($totalField))
                    {
                        // initialize the temp value
                        $tempValue = 0;

                        // get the value from the return array
                        if (isset($returnOrderTotals[$totalField]))
                        {
                            $tempValue = $returnOrderTotals[$totalField];
                            if (empty($tempValue))
                            {
                                $tempValue = 0;
                            }
                        }

                        // check to see if this is a discount value as it is on
                        // a different table
                        if ($totalField == 'hdr_disc_fixed_amount')
                        {
                            // check to see if there is a discount code
                            // as this field is only for discounts
                            $couponCode = $this->getData('coupon_code');
                            if(!empty($couponCode))
                            {
                                // get the order discount from the coupon code
                                $orderDiscount = $this->_discountHelper->DiscountCode($couponCode);

                                // add the original value and then set the new value
                                // to the value on the array + the new value
                                $returnOrderTotals[$totalField] = $tempValue + $orderDiscount[$totalField];
                            }
                        }
                        else
                        {
                            // get the value from order
                            $tempTotal = $magentoOrder->getData($totalField);
                            if (empty($tempTotal))
                            {
                                $tempTotal = 0;
                            }

                            // add the original value and then set the new value
                            // to the value on the array + the new value
                            $returnOrderTotals[$totalField] = $tempValue + $tempTotal;
                        }
                    }
                }
            }
        }

        // return
        return $returnOrderTotals;
    }
    
    /**
     * @inheritdoc
     *
     * Adds the object to the status history collection, which is automatically saved when the order is saved.
     * See the entity_id attribute backend model.
     * Or the history record can be saved standalone after this.
     *
     * @param \Magento\Sales\Model\Order\Status\History $history
     * @return $this
     */
    public function addStatusHistory(\Magento\Sales\Model\Order\Status\History $history)
    {  
        parent::addStatusHistory($history);
        
        if(!empty($comment = $history->getData('comment'))){
          $this->_orderService->postOrderCommentNote($this->getIncrementId(),$comment);
        }
       
    }
}