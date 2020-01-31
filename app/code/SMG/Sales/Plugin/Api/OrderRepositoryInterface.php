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
     * OrderRepositoryInterface constructor.
     *
     * @param LoggerInterface $logger
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     */
    public function __construct(LoggerInterface $logger,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        ProductFactory $productFactory,
        ProductResource $productResource)
    {
        $this->_logger = $logger;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
    }

    public function beforeSave(\Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderInterface $order)
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
            foreach($items as $item)
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
                    $statesNotAllowed = $product->getStatesNotAllowed();

                    //check the state values from product
                   if (!empty($statesNotAllowed))
                   {
                    $statesNotAllowedList = explode(',', $statesNotAllowed);

                    // initialize the list of
                    $statesNotAllowedArray = array();
                    foreach ($statesNotAllowedList as $stateNotAllowed)
                    {
                        $attr = $this->_productResource->getAttribute('state_not_allowed');
                        $statesNotAllowedArray = [];

                        try
                        {
                            $statesNotAllowedArray[] = $attr->getSource()->getOptionText($stateNotAllowed);
                        }
                        catch (\Magento\Framework\Exception\LocalizedException $e)
                        {
                            $this->_logger->error($e);
                        }
                    }

                    // is the state in the list
                    if (in_array($state, $statesNotAllowedArray)) {
                        $validate = true;
                    }
                   }
                }
            }

            if($validate)
            {
                echo $message = "Unfortunately one or more of the selected products is restricted from shipping to " . $state . ".";
                throw new InputException(__($message));
                die();
            }
        }

        // we must return the same number of parameters as the original method
        return [$order];
    }
}
