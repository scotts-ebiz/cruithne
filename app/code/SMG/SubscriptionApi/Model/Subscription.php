<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Exception\SecurityViolationException;
use SMG\SubscriptionApi\Api\SubscriptionInterface;

class Subscription implements SubscriptionInterface
{

    /**
     * @var /SMG/Api/Helper/QuizHelper
     */
    protected $_helper;
    protected $_customerSession;
    protected $_formKey;
    protected $_cart;
    protected $_product;
    protected $_productRepository;
    protected $_resultJsonFactory;
    protected $_checkoutSession;

    public function __construct(
        \SMG\RecommendationApi\Helper\RecommendationHelper $helper,
        \Magento\Checkout\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->_helper = $helper;
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
        $this->_productRepository = $productRepository;
    }

    /**
     * Process quiz data, build order object and send customer to checkout. Note that we are hijacking the cart for
     * the addition of subscriptions and to make the display easier.
     * @todo Wes this needs to be refactored. We should be able to just add all of the orders for any
     *
     * @param string $key
     * @param string $subscription_plan
     * @param mixed $data
     * @param mixed $addons
     * @return array|false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @api
     */
    public function addSubscriptionToCart($key, $subscription_plan, $data, $addons)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Before starting to add new products, let's clear customer's cart
        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
        foreach( $quoteItems as $item ) {
            $this->_cart->removeItem($item->getId())->save();
        }

        // We will have to calculate the price differently for the subscription than we normally would
        $totalSubscriptionPrice = 0;

        // Add "Annual Subscription" product if the customer selected the annual subscription plan
        if( $subscription_plan == 'annual' ) {
            try {
                $_product = $this->_productRepository->get( 'annual' );
                $productId = $_product->getId();
                $params = array(
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => 1,
                );
                $this->_cart->addProduct( $productId, $params );
            } catch( Exception $e ) {
                $response = array( 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage());
                return json_encode( $response );
            }
        }

        // Go through all the core products, add them to cart and calculate
        // the total subscription price which will be applied to the Annual Subscription product
        if( ! empty( $data['plan']['coreProducts'] ) ) {
            
            $coreProducts = $data['plan']['coreProducts'];
            $firstApplicationStartDate = $coreProducts[0]['applicationStartDate'];

            foreach( $coreProducts as $product ) {
                
                try {
                    $_product = $this->_productRepository->get( $product['sku'] );
                    $totalSubscriptionPrice += $_product->getPrice();
                    $seasonalProductSku = $this->getSeasonalProductSku( $product['season'] );
                    $seasonalProduct = $this->_productRepository->get( $seasonalProductSku );
                    $params = array(
                        'form_key'  => $this->_formKey->getFormKey(),
                        'qty'       => 1,
                    );
                    $this->_cart->addProduct( $seasonalProduct->getId(), $params );
                
                    // If Seasonal subscription, add only the first core product
                    if( $subscription_plan == 'seasonal' ) {
                        break;
                    }
                } catch( Exception $e ) {
                    $response = array( 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage());
                    return json_encode( $response );
                }
            }
        }

        // Go through all selected AddOn Products and add them to the cart
        foreach( $addons as $addon ) {
            try {
                $_product = $this->_productRepository->get( $addon );
                $productId = $_product->getId();
                $params = array(
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => 1,
                );
                $this->_cart->addProduct( $productId, $params );
            } catch( Exception $e ) {
                $response = array( 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage());
                return json_encode( $response );
            }
        }

        // Apply discount code for all annual subscriptions
        if( $subscription_plan == 'annual' ) {
            $this->_checkoutSession->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
        }

        // Save cart
        $this->_cart->save();

        // Go through the cart items and modify their prices for the current customer order
        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
        foreach( $quoteItems as $item ) {
            // Apply the total price from the core products to the annual subscription product
            if( $subscription_plan == 'annual' ) {
                if( $item->getSku() == 'annual' ) {
                    $item->setCustomPrice($totalSubscriptionPrice);
                    $item->setOriginalCustomPrice($totalSubscriptionPrice);
                    $item->getProduct()->setIsSuperMode(true);
                }
            } else {
                $seasonalSkus = array( 'early-spring', 'late-spring', 'early-summer', 'early-fall' );
                if( in_array( $item->getSku(), $seasonalSkus ) ) {
                    $item->setCustomPrice($totalSubscriptionPrice);
                    $item->setOriginalCustomPrice($totalSubscriptionPrice);
                    $item->getProduct()->setIsSuperMode(true);
                }
            }
        }

        // Update Cart
        $this->_cart->save();

        foreach ( $this->_cart->getItems() as $item ) {
            $items[] = $item->getName() . " " . $item->getSku() . " qty: " . $item->getQty() . " addon: " . (String)$item->getAddon() . " price: " .  $item->getPrice();
        }

        $response = array( 'success' => true, 'estimated_arrival' => $this->getEstimatedArrivalDate($firstApplicationStartDate) );

        return json_encode( $response );
    }

    /**
     * Calculate estimated arrival date
     * 
     * @param DateTime $start_date
     * @return DateTime
     */
    private function getEstimatedArrivalDate($start_date)
    {
        $applicationStartDate = new \DateTime($start_date);
        $applicationStartDate->sub(new \DateInterval('P9D'));
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        return ( $todayDate <= $applicationStartDate ) ? $applicationStartDate->format('m/d/Y') : $todayDate->format('m/d/Y');
    }

    /**
     * Return SKU code for the product based on the season name
     * 
     * @param string $season_name
     * @return string
     */
    private function getSeasonalProductSku($season_name)
    {
        switch($season_name) {
            case 'Early Spring Feeding':
                return 'early-spring';
            case 'Late Spring Feeding':
                return 'late-spring';
            case 'Early Summer Feeding':
                return 'early-summer';
            case 'Early Fall Feeding':
                return 'early-fall';
            default:
                return '';
        }
    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     */
    public function formValidation($key) {
        return $this->_formKey->getFormKey() !== $key;
    }

}
