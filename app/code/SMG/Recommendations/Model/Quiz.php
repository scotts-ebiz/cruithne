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

    public function __construct(
        \SMG\Recommendations\Helper\QuizHelper $helper,
        \Magento\Checkout\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_helper = $helper;
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        $this->_cart = $cart;
        $this->_product = $product;
        $this->_productRepository = $productRepository;
        $this->_resultJsonFactory = $resultJsonFactory;
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
        // Clear cart
        $this->_cart->truncate()->save();

        // Add all core products to the Annual subscription
        if( $subscription_plan == 'annual' ) {
            $coreProducts = $data['plan']['coreProducts']; // Get core products
            foreach( $coreProducts as $product ) {
                $_product = $this->_productRepository->get( $product['sku'] );
                if( $_product ) {
                    $this->_cart->addProduct( $_product, array(
                        'form_key'  => $this->_formKey->getFormKey(),
                        'product'   => $_product->getId(),
                        'qty'       => 1
                    ) );
                }
            }
            die();
        }

        // Add selected addon products
        if( ! empty( $addons ) ) {
            foreach( $addons as $addon ) {
                $_product = $this->_productRepository->get( $addon );
                if( $_product ) {
                    $this->_cart->addProduct( $_product, array(
                        'form_key'  => $this->_formKey->getFormKey(),
                        'product'   => $_product->getId(),
                        'qty'       => 1
                    ) );
                }
            }
        }

        $this->_cart->save();

        $response = $this->_resultJsonFactory->create();
        $response->setData( [ 'success' => true ] );

        return $response;
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
