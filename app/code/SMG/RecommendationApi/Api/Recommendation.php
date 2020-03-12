<?php

namespace SMG\RecommendationApi\Api;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Webapi\Request;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SMG\RecommendationApi\Api\Interfaces\RecommendationInterface;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\Collection as SubscriptionCollection;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription;

/**
 * Class Recommendation
 * @package SMG\RecommendationApi\Api
 */
class Recommendation implements RecommendationInterface
{
    /**
     * @var RecommendationHelper
     */
    protected $_recommendationHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var JsonFactory
     */
    protected $_jsonResultFactory;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @var array
     */
    protected $_products = [];

    /**
     * @var Subscription
     */
    protected $_subscription;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * @var Response
     */
    protected $_response;

    /**
     * Recommendation constructor.
     * @param RecommendationHelper $recommendationHelper
     * @param StoreManagerInterface $storeManager
     * @param FormKey $formKey
     * @param Request $request
     * @param JsonFactory $jsonResultFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SessionManagerInterface $coreSession
     * @param Subscription $subscription
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param Response $response
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function __construct(
        RecommendationHelper $recommendationHelper,
        StoreManagerInterface $storeManager,
        FormKey $formKey,
        Request $request,
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        SessionManagerInterface $coreSession,
        Subscription $subscription,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        Response $response
    ) {
        $this->_recommendationHelper = $recommendationHelper;
        $this->_storeManager = $storeManager;
        $this->_formKey = $formKey;
        $this->_request = $request;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->_productRepository = $productRepository;
        $this->_coreSession = $coreSession;
        $this->_subscription = $subscription;
        $this->_logger = $logger;
        $this->_customerSession = $customerSession;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_response = $response;

        // Check to make sure that the module is enabled at the store level
        if (! $this->_recommendationHelper->isActive($this->_storeManager->getStore()->getId())) {
            throw new NotFoundException(__('File not Found'));
        }
    }

    /**
     * Get quiz template and store it's id in session
     *
     * @param string $key
     * @return mixed
     * @throws SecurityViolationException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     *
     * @api
     */
    public function new($key)
    {

        // Test the form key
        if (! $this->formValidation($key)) {
            $this->_logger->error('Form key validation error.');
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Check to make sure that the config is set up
        if (! $this->_recommendationHelper->getNewQuizApiPath()) {
            $error = 'Quiz New API Path not set in config.';
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }

        // Get the response
        $url = filter_var($this->_recommendationHelper->getNewQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';
        $response = $this->request($url, $data, $method);

        // Check the response
        if (empty($response)) {
            $error = 'Recommendation Engine response for new quiz template is malformed';
            $this->_logger->error($error . ": " . json_encode($response));
            throw new LocalizedException(__($error));
        }

        // Set Quiz Template ID
        if (! $this->_coreSession->getQuizTemplateId()) {
            $this->_coreSession->setQuizTemplateId($response[0]['id']);
        }

        return $response;
    }

    /**
     * Send answers to complete quiz
     *
     * @param mixed $key
     * @param mixed $id
     * @param mixed $answers
     * @param string $zip
     * @param string $lawnType
     * @param string $lawnSize
     * @return mixed
     * @throws SecurityViolationException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     *
     * @api
     */
    public function save($key, $id, $answers, $zip, $lawnType, $lawnSize)
    {
        // Test the form key
        if (! $this->formValidation($key)) {
            $this->_logger->error('Form key validation error.');
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Check to see if the id is already processed and return result if so
        if ($id == $this->_coreSession->getQuizId()) {
            $this->_logger->info("Save called but quiz already exists with id: " . $id . ". Redirected to getResult().");
            return $this->getResult($key, $id, $zip, $lawnType, $lawnSize);
        }

        // Make sure that the config path is set up
        if (! $this->_recommendationHelper->getSaveQuizApiPath()) {
            $error = 'Quiz Save API Path not set in config.';
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }

        // Make sure we have the necessary input
        if (empty($answers) || empty($id)) {
            $error = 'Invalid input for save endpoint';
            $this->_logger->error($error . ": id: " . $id . " answers: " . json_encode($answers));
            throw new LocalizedException(__($error));
        }

        // Get the response
        $url = trim($this->_recommendationHelper->getSaveQuizApiPath(), '/');
        $url = filter_var(str_replace('{quizTemplateId}', $id, $url), FILTER_SANITIZE_URL);
        $method = 'POST';
        $response = $this->request($url, ['answers' => $answers], $method);

        // Check that the response is good
        if (empty($response) || ! isset($response[0]['id'])) {
            $error = 'Recommendation Engine returned a malformed or invalid response';
            $this->_logger->error($error . ": " . json_encode($response));
            throw new LocalizedException(__($error));
        }

        // Get the subscription
        try {
            $this->findOrCreateSubscription(
                $response,
                $zip,
                $lawnSize,
                $lawnType
            );
        } catch (\Exception $e) {
            $error = "Failed to find or create a subscription";
            $this->_logger->error($error . ": " . $e->getMessage());
            throw new LocalizedException(__($error));
        }

        // Get the product flat file so it is accessible.
        $this->getProducts($key);
        $this->mapProducts($response[0]['plan']['coreProducts']);
        $this->mapProducts($response[0]['plan']['addOnProducts']);

        // Add to recommendation to core session
        $this->_coreSession->setQuizId($response[0]['id']);
        $this->_coreSession->setZipCode($zip);

        return $response;
    }

    /**
     * Returns quiz data by id.
     *
     * @param mixed $key
     * @param string $id
     * @param string $zip
     * @param string $lawnType
     * @param int $lawnSize
     * @return mixed
     * @throws SecurityViolationException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @api
     */
    public function getResult($key, $id, $zip, $lawnType = '', $lawnSize = 0)
    {
        // Test the form key
        if (!$this->formValidation($key)) {
            $this->_logger->error('Form key validation error.');
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Make sure that the config path is available
        if (!$this->_recommendationHelper->getQuizResultApiPath()) {
            $error = 'Quiz Result API Path not set in config.';
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }

        // Check that we have minimum input
        if (empty($id)) {
            $error = 'Minimum input invalid for getting result';
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }

        // Get the response
        $id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $url = filter_var(
            trim(
                str_replace('{completedQuizId}', $id, $this->_recommendationHelper->getQuizResultApiPath()),
                '/'
            ),
            FILTER_SANITIZE_URL
        );
        $response = $this->request($url, '', 'GET');

        // Check that the response is valid
        if (empty($response) || ! isset($response[0]['id'])) {
            $error = 'Recommendation Engine returned a malformed or invalid response';
            $this->_logger->error($error . ": " . json_encode($response));
            throw new LocalizedException(__($error));
        }

        // Get the subscription
        try {
            $subscription = $this->findOrCreateSubscription(
                $response,
                $zip,
                $lawnSize,
                $lawnType
            );
        } catch (\Exception $e) {
            $error = "Failed to find or create a subscription";
            $this->_logger->error($error . ": " . $e->getMessage());
            throw new LocalizedException(__($error));
        }

        // Get the product flat file so it is accessible.
        $this->getProducts($key);
        $this->mapProducts($response[0]['plan']['coreProducts']);
        $this->mapProducts($response[0]['plan']['addOnProducts']);

        // Set the session variables
        $this->_coreSession->setQuizId($response[0]['id']);
        $this->_coreSession->setZipCode($zip);

        $response[0]['subscription'] = [
            'lawn_size' => $subscription->getData('lawn_size'),
            'lawn_zip' => $subscription->getData('lawn_zip'),
            'status' => $subscription->getData('subscription_status'),
            'zone_name' => $subscription->getData('zone_name'),
        ];

        return $response;
    }

    /**
     * Return completed quizzes
     *
     * @param mixed $key
     * @return mixed
     * @throws SecurityViolationException
     * @throws NoSuchEntityException
     * @api
     */
    public function getCompleted($key)
    {
        // Test the form key
        if (! $this->formValidation($key)) {
            $this->_logger->error('Form key validation error.');
            throw new SecurityViolationException(__('Unauthorized'));
        }

        if (! $this->_recommendationHelper->getCompletedQuizApiPath()) {
            return;
        }

        $url = filter_var($this->_recommendationHelper->getCompletedQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->_request($url, $data, $method);

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * Map the quiz to the user
     *
     * @param string $key
     * @param string $user_id
     * @param string $quiz_id
     * @return mixed
     * @throws SecurityViolationException
     * @throws NoSuchEntityException
     * @api
     */
    public function mapToUser($key, $user_id, $quiz_id)
    {

        // Test the form key
        if (! $this->formValidation($key)) {
            $this->_logger->error('Form key validation error.');
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Make sure we have a path
        if (!$this->_recommendationHelper->getMapToUserPath()) {
            return;
        }

        if (empty($user_id) || empty($quiz_id)) {
            return;
        }

        try {
            $url = filter_var($this->_recommendationHelper->getMapToUserPath(), FILTER_SANITIZE_URL);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-userid: ' . $user_id,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [$quiz_id]);

            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage() . ' (' . $e->getCode() . ')';
        }
    }

    /**
     * Get the products flat file
     *
     * @param $key
     * @return mixed
     * @throws SecurityViolationException
     * @throws NoSuchEntityException
     * @api
     */
    public function getProducts($key)
    {
        // Test the form key
        if (! $this->formValidation($key)) {
            $this->_logger->error('Form key validation error.');
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // We already accessed the products previously, so just return those.
        if (count($this->_products)) {
            return [$this->_products];
        }

        // Make sure we have a path
        if (!$this->_recommendationHelper->getProductsPath()) {
            return;
        }

        $url = filter_var($this->_recommendationHelper->getProductsPath(), FILTER_SANITIZE_URL);
        $response = $this->request($url, '', 'GET');

        if (empty($response)) {
            return null;
        }

        $products = [];
        foreach ($response[0] as $product) {
            $products[$product['sku']] = $product;
        }

        $this->_products = $products;

        return [$products];
    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     * @throws NoSuchEntityException
     */
    public function formValidation($key)
    {
        if ($this->_recommendationHelper->useCsrf($this->_storeManager->getStore()->getId())) {
            return $this->_formKey->getFormKey() === $key;
        }
        return true;
    }

    /**
     * Curl wrapper
     *
     * @param string $url
     * @param $data
     * @param string $method
     * @return array|null
     * @throws Exception
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
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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

    /**
     * Load a subscription if it exists or create it based on response.
     *
     * @param $response
     * @param $zip
     * @param $lawnSize
     * @param $lawnType
     * @return mixed|\SMG\SubscriptionApi\Model\Subscription
     * @throws Exception
     */
    protected function findOrCreateSubscription($response, $zip, $lawnSize, $lawnType)
    {
        // Check to see if subscription already exists
        try {
            $subscription = $this->_subscription->getSubscriptionByQuizId($response[0]['id']);
            /**
             * If this subscription status is not pending, we need to return an error.
             * @author Sean Kegel
             * @date 12/13/2019
             */
        } catch (\Exception $e) {
            // Nope. Make it.
            $subscription = $this->_subscription->createFromRecommendation($response, $zip, $lawnSize, $lawnType);
        }

        return $subscription;
    }

    /**
     * Get the Magento and flat file product information.
     *
     * @param array $products
     */
    protected function mapProducts(&$products = [])
    {
        $products = array_map(
            function ($product) {
                try {
                    $model = $this->_productRepository->get($product['sku']);
                    $model = $model->toFlatArray();
                } catch (NoSuchEntityException $ex) {
                    $model = [];
                }

                $info = isset($this->_products[$product['sku']])
                    ? $this->_products[$product['sku']]
                    : [];

                return array_merge(
                    $model,
                    $info,
                    $product
                );
            },
            $products
        );
    }
}
