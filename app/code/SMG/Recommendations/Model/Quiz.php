<?php

namespace SMG\Recommendations\Model;

use SMG\Recommendations\Api\QuizInterface;

class Quiz implements QuizInterface
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
        \SMG\Recommendations\Helper\QuizHelper $helper,
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
     * Get quiz template and store it's id in session
     *
     * @api
     * @return array|null
     */
    public function new()
    {
        if (! $this->_helper->getNewQuizApiPath()) {
            return;
        }

        $url = filter_var($this->_helper->getNewQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request($url, $data, $method);

        if (! empty($response)) {
            if (! isset($_SESSION['quiz_template_id'])) {
                $_SESSION['quiz_template_id'] = $response[0]['id'];
            }

            return $response;
        }

        return;
    }

    /**
     * Send answers to complete quiz
     *
     * @param $id
     * @param $answers
     *
     * @return array|null
     * @api
     */
    public function save($id, $answers)
    {
        if (! $this->_helper->getSaveQuizApiPath() || empty($answers) || empty($id)) {
            return null;
        }

        $url = trim($this->_helper->getSaveQuizApiPath(), '/');
        $url = str_replace('{quizTemplateId}', $id, $url);
        $baseUrl = filter_var($url, FILTER_SANITIZE_URL);
        $method = 'POST';

        $response = $this->request($url, ['answers' => $answers], $method);

        if (! empty($response)) {
            return $response;
        }

        return null;
    }

    /**
     * Returns quiz data by id.
     *
     * @param string $id
     * @return array
     *
     * @api
     */
    public function getResult($id)
    {

        //getQuizResultApiPath
        if (empty($id) || ! $this->_helper->getQuizResultApiPath()) {
            return;
        }

        $id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);

        $url = filter_var(
            trim(
                str_replace('{completedQuizId}', $id, $this->_helper->getQuizResultApiPath()),
                '/'
            ),
            FILTER_SANITIZE_URL
        );

        $response = $this->request($url, '', 'GET');

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * Return completed quizzes
     *
     * @return array
     *
     * @api
     */
    public function getCompleted()
    {
        if (! $this->_helper->getCompletedQuizApiPath()) {
            return;
        }

        $url = filter_var($this->_helper->getCompletedQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request($url, $data, $method);

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * Process quiz data, build order object and send customer to checkout
     * 
     * @return array
     * 
     * @api
     */
    public function processOrder($subscription_plan, $data, $addons)
    {
        // Before starting to add new products, let's clear customer's cart
        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
        foreach( $quoteItems as $item ) {
            $this->_cart->removeItem($item->getId())->save();
        }

        $total_subscription_price = 0;

        /**
         * Add "Annual Subscription" product if the customer selected the annual subscription plan
         */
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

        /**
         * Go through all the core products, add them to cart and calculate
         * the total subscription price which will be applied to the Annual Subscription product
         */
        if( ! empty( $data['plan']['coreProducts'] ) ) {
            $coreProducts = $data['plan']['coreProducts'];
            foreach( $coreProducts as $product ) {
                try {
                    $_product = $this->_productRepository->get( $product['sku'] );
                    $productId = $_product->getId();
                    $params = array(
                        'form_key'  => $this->_formKey->getFormKey(),
                        'qty'       => 1,
                    );
                    $this->_cart->addProduct( $productId, $params );
                    $total_subscription_price += $_product->getPrice();
                    // If it's seasonal subscription, add only the first product
                    if( $subscription_plan == 'seasonal' ) {
                        $seasonal_product_sku = $this->getSeasonalProductSku( $product['season'] );
                        $seasonal_product = $this->_productRepository->get( $seasonal_product_sku );
                        $seasonal_poduct_params = array(
                            'form_key'  => $this->_formKey->getFormKey(),
                            'qty'       => 1,
                        );
                        $this->_cart->addProduct( $seasonal_product->getId(), $seasonal_poduct_params );
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
            if( $item->getSku() == 'annual' ) {
                $item->setCustomPrice($total_subscription_price);
                $item->setOriginalCustomPrice($total_subscription_price);
                $item->getProduct()->setIsSuperMode(true);
            }

            // If it's Seasonal subscription, apply the total price from the core product to the seasonal product
            if( $subscription_plan == 'seasonal' ) {
                $seasonal_skus = array( 'early-spring', 'late-spring', 'early-summer', 'early-fall' );
                if( in_array( $item->getSku(), $seasonal_skus ) ) {
                    $item->setCustomPrice($total_subscription_price);
                    $item->setOriginalCustomPrice($total_subscription_price);
                    $item->getProduct()->setIsSuperMode(true);
                }
            }
            
            // Set price to 0$ for all core products
            if( $item->getProduct()->getAttributeText('is_addon') != 'Yes' ) {
                $item->setCustomPrice(0);
                $item->setOriginalCustomPrice(0);
                $item->getProduct()->setIsSuperMode(true);
            }
        }

        // Update Cart
        $this->_cart->save();
        
        $response = array( 'success' => true );

        return json_encode( $response );
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
     * cURL wrapper
     *
     * @param string $url
     * @param string $method
     * @return array|null
     */
    private function request($url, $data, $method = '')
    {
        if (! empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                if ($method == 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    if (! empty($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                } elseif ($method == 'PUT') {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    if (! empty($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } else {
                    curl_setopt($ch, CURLOPT_POST, false);
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json; charset=utf-8',
                    'Accept: application/json',
                ]);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    throw new Exception(curl_error($ch));
                }

                curl_close($ch);

                // Wrap in an array because Magento strips off the top level
                // keys for some random reason.
                return [json_decode($response, true)];
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }

        return;
    }
}
