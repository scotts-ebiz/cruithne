<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 7/3/19
 * Time: 12:09 PM
 */

namespace SMG\Sales\Plugin\Api;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Setup\Exception;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;

class OrderRepositoryInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var QuoteResource
     */
    protected $_quoteResource;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var ProductResource
     */
    protected $_productResource;

    /**
     * @var OrderExtensionFactory
     */
    protected $_extensionFactory;
    
    /**
     * @var SubscriptionOrderCollectionFactory
     */
    protected $_subscriptionOrderCollectionFactory;

    /**
     * OrderRepositoryInterface constructor.
     *
     * @param LoggerInterface $logger
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param OrderExtensionFactory $extensionFactory
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     */
    public function __construct(LoggerInterface $logger,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        ProductFactory $productFactory,
        ProductResource $productResource,
        OrderExtensionFactory $extensionFactory,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory)
    {
        $this->_logger = $logger;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
        $this->_extensionFactory = $extensionFactory;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
    }

    public function beforeSave(\Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderInterface $order)
    {
        // we only want to check the restricted states if this is a new order
        // if there is no entity id then it is a new order and it hasn't been
        // saved to the database.
        if (empty($order->getEntityId()))
        {
            // initialize the state
            $state = '';

            // get the quote id to get the needed information for the order
            $quoteId = $order->getQuoteId();

            /**
             * @var \Magento\Quote\Model\Quote $quote
             */
            $quote = $this->_quoteFactory->create();
            $this->_quoteResource->load($quote, $quoteId);

            /**
             * @var \Magento\Quote\Model\Quote\Address $shippingAddress
             */
            $shippingAddress = $quote->getShippingAddress();
            if (!empty($shippingAddress))
            {
                $state = $shippingAddress->getRegion();
            }

            // if the state is empty then use the order shipping state
            if (empty($state))
            {
                /**
                 * @var \Magento\Sales\Api\Data\OrderAddressInterface $shippingAddress
                 */
                $shippingAddress = $order->getShippingAddress();
                if (!empty($shippingAddress))
                {
                    $state = $shippingAddress->getRegion();
                }
            }

            // if the state is empty then use the billing state
            if (empty($state))
            {
                /**
                 * @var \Magento\Sales\Api\Data\OrderAddressInterface $billingAddress
                 */
                $billingAddress = $order->getBillingAddress();
                if (!empty($billingAddress))
                {
                    $state = $billingAddress->getRegion();
                }
            }

            // make sure that the state is not empty
            if (!empty($state))
            {
                // initialize
                $validate = false;

                // loop through the items
                /**
                 * @var \Magento\Sales\Api\Data\OrderItemInterface[] $items
                 */
                $items = $order->getItems();
                foreach ($items as $item)
                {
                    // get the product id to load the product info
                    $productId = $item->getProductId();

                    // get the product
                    /**
                     * @var \Magento\Catalog\Model\Product $product
                     */
                    $product = $this->_productFactory->create();
                    $this->_productResource->load($product, $productId);

                    // make sure that there is a product for continuing
                    if (!empty($product))
                    {
                        // get the allowed states
                        $statesNotAllowed = $product->getData('state_not_allowed');

                        //check the state values from product
                        if (!empty($statesNotAllowed))
                        {
                            $statesNotAllowedList = explode(',', $statesNotAllowed);

                            // initialize the list of
                            $statesNotAllowedArray = [];
                            $notAllowedProductState = [];
                            foreach ($statesNotAllowedList as $stateNotAllowed)
                            {
                                $attr = $this->_productResource->getAttribute('state_not_allowed');

                                try
                                {
                                    $statesNotAllowedArray[] = $attr->getSource()->getOptionText($stateNotAllowed);
                                    $notAllowedProductState[] = "SKU: ".
                                        $product->getData('sku') .
                                        " for State: " .
                                        $attr->getSource()->getOptionText($stateNotAllowed);

                                } catch (\Magento\Framework\Exception\LocalizedException $e)
                                {
                                    $this->_logger->error($e);
                                }
                            }

                            // is the state in the list
                            if (in_array($state, $statesNotAllowedArray))
                            {
                                $validate = true;
                            }
                        }
                    }
                }

                if ($validate)
                {
                    echo $message = "Unfortunately one or more of the selected products is restricted from shipping : " . implode(', ', $notAllowedProductState) . ".";
                    throw new InputException(__($message));
                    die();
                }
            }
        }

        // we must return the same number of parameters as the original method
        return [$order];
    }

    public function afterGet(\Magento\Sales\Api\OrderRepositoryInterface $subject, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getSubscriptionId() && $extensionAttributes->getMasterSubscriptionId() && $extensionAttributes->getShipStartDate() && $extensionAttributes->getShipEndDate()) {
            return $order;
        }
        
        $subscriptionOrderCollection = $this->_subscriptionOrderCollectionFactory->create();

        $subscriptionOrder = $subscriptionOrderCollection
            ->addFieldToFilter('sales_order_id', $order->getId())
            ->getFirstItem();
        
        $orderExtension = $extensionAttributes ? $extensionAttributes : $this->_extensionFactory->create();
        $orderExtension->setSubscriptionId($order->getSubscriptionId());
        $orderExtension->setMasterSubscriptionId($order->getData('master_subscription_id'));
        $orderExtension->setShipStartDate($order->getData('ship_start_date'));
        $orderExtension->setShipEndDate($order->getData('ship_end_date'));
        if ($subscriptionOrder || $subscriptionOrder->getId()) {
            $orderExtension->setApplicationStartDate($subscriptionOrder->getData('application_start_date'));
            $orderExtension->setApplicationEndDate($subscriptionOrder->getData('application_end_date'));
        }
        $order->setExtensionAttributes($orderExtension);
        return $order;
    }

    public function afterGetList(\Magento\Sales\Api\OrderRepositoryInterface $subject, \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult)
    {
        $orders = $searchResult->getItems();
        foreach ($orders as &$order) {
            $this->afterGet($subject, $order);
        }
        return $searchResult;
    }
}
