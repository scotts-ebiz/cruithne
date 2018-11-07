<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;

use Vantiv\Payment\Gateway\Common\Renderer\PaypalAuthorizationRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\PaypalCaptureRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\PaypalAuthReversalRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\PaypalSaleRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\PaypalCreditRenderer;

use Vantiv\Payment\Gateway\Paypal\Parser\PaypalAuthorizeResponseParserFactory;
use Vantiv\Payment\Gateway\Paypal\Parser\PaypalCaptureResponseParserFactory;
use Vantiv\Payment\Gateway\Paypal\Parser\PaypalVoidResponseParserFactory;
use Vantiv\Payment\Gateway\Paypal\Parser\PaypalSaleResponseParserFactory;
use Vantiv\Payment\Gateway\Paypal\Parser\PaypalCreditResponseParserFactory;

/**
 * Certification test model
 */
class PayPalTest extends AbstractTest
{
    /**
     * @var string
     */
    const PATH_PREFIX = 'payment/vantiv_paypal_express/';

    /**
     * @var string
     */
    const ID = 'vantiv_paypal_express';

    /**
     * Certification Test Command
     *
     * @var Command
     */
    private $command;

    /**
     * Cache of response data.
     *
     * @var array
     */
    private $cachedResponseData = [];

    /**
     * @var PaypalAuthorizationRenderer
     */
    private $paypalAuthorizationRenderer = null;

    /**
     * @var PaypalCaptureRenderer
     */
    private $paypalCaptureRenderer = null;

    /**
     * @var PaypalAuthReversalRenderer;
     */
    private $paypalAuthReversalRenderer = null;

    /**
     * @var PaypalSaleRenderer;
     */
    private $paypalSaleRenderer = null;

    /**
     * @var PaypalCreditRenderer;
     */
    private $paypalCreditRenderer = null;

    /**
     * @var PaypalAuthorizeResponseParserFactory
     */
    private $paypalAuthorizeResponseParserFactory = null;

    /**
     * @var PaypalCaptureResponseParserFactory
     */
    private $paypalCaptureResponseParserFactory = null;

    /**
     * @var PaypalVoidResponseParserFactory
     */
    private $paypalAuthReversalResponseParserFactory = null;

    /**
     * @var PaypalSaleResponseParserFactory
     */
    private $paypalSaleResponseParserFactory = null;

    /**
     * @var PaypalCreditResponseParserFactory
     */
    private $paypalCreditResponseParserFactory = null;

    /**
     * Constructor
     *
     * @param Config $config
     * @param TestResponseParserFactory $parserFactory
     * @param Command $command
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param VantivConfig $vantivConfig
     * @param PaypalAuthorizationRenderer $paypalAuthorizationRenderer
     * @param PaypalCaptureRenderer $paypalCaptureRenderer
     * @param PaypalAuthReversalRenderer $paypalAuthReversalRenderer
     * @param PaypalSaleRenderer $paypalSaleRenderer
     * @param PaypalCreditRenderer $paypalCreditRenderer
     * @param PaypalAuthorizeResponseParserFactory $paypalAuthorizeResponseParserFactory
     * @param PaypalCaptureResponseParserFactory $paypalCaptureResponseParserFactory
     * @param PaypalVoidResponseParserFactory $paypalAuthReversalResponseParserFactory
     * @param PaypalSaleResponseParserFactory $paypalSaleResponseParserFactory
     * @param PaypalCreditResponseParserFactory $paypalCreditResponseParserFactory
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        Command $command,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        PaypalAuthorizationRenderer $paypalAuthorizationRenderer,
        PaypalCaptureRenderer $paypalCaptureRenderer,
        PaypalAuthReversalRenderer $paypalAuthReversalRenderer,
        PaypalSaleRenderer $paypalSaleRenderer,
        PaypalCreditRenderer $paypalCreditRenderer,
        PaypalAuthorizeResponseParserFactory $paypalAuthorizeResponseParserFactory,
        PaypalCaptureResponseParserFactory $paypalCaptureResponseParserFactory,
        PaypalVoidResponseParserFactory $paypalAuthReversalResponseParserFactory,
        PaypalSaleResponseParserFactory $paypalSaleResponseParserFactory,
        PaypalCreditResponseParserFactory $paypalCreditResponseParserFactory,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $parserFactory,
            $storeManager,
            $vantivConfig,
            $resultFactory,
            $data
        );

        $this->command = $command;
        $this->paypalAuthorizationRenderer = $paypalAuthorizationRenderer;
        $this->paypalCaptureRenderer = $paypalCaptureRenderer;
        $this->paypalAuthReversalRenderer = $paypalAuthReversalRenderer;
        $this->paypalSaleRenderer = $paypalSaleRenderer;
        $this->paypalCreditRenderer = $paypalCreditRenderer;
        $this->paypalAuthorizeResponseParserFactory = $paypalAuthorizeResponseParserFactory;
        $this->paypalCaptureResponseParserFactory = $paypalCaptureResponseParserFactory;
        $this->paypalAuthReversalResponseParserFactory = $paypalAuthReversalResponseParserFactory;
        $this->paypalSaleResponseParserFactory = $paypalSaleResponseParserFactory;
        $this->paypalCreditResponseParserFactory = $paypalCreditResponseParserFactory;
    }

    /**
     * Get path prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PATH_PREFIX;
    }

    /**
     * Get feature id
     *
     * @return string
     */
    public function getId()
    {
        return self::ID;
    }

    /**
     * Run PayPal Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeAuthorizationTests();
        $this->executeCaptureTests();
        $this->executeAuthReversalTests();
        $this->executeSaleTests();
        $this->executeCreditTests();
    }

    /**
     * Run Authorization tests.
     */
    private function executeAuthorizationTests()
    {
        /*
         * Test PayPal Authorization transactions.
         */
        $renderer = $this->getAuthorizationRenderer();

        foreach ($this->getAuthorizationRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createAuthorizationParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Authorization, Dataset "' . $id . '"',
                'store_id'     => $this->storeManager->getStore()->getId(),
                'merchant_id'  => $this->getVantivConfig()->getValue('merchant_id'),
                'order_id'     => $id,
                'litle_txn_id' => $parser->getLitleTxnId(),
                'success_flag' => $success,
                'request'      => $request,
                'response'     => $response,
            ]);
        }
    }

    /**
     * Run Capture tests.
     */
    private function executeCaptureTests()
    {
        /*
         * Test PayPal Capture transactions.
         */
        $renderer = $this->getCaptureRenderer();

        foreach ($this->getCaptureRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createCaptureParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Capture, Dataset "' . $id . '"',
                'store_id'     => $this->storeManager->getStore()->getId(),
                'merchant_id'  => $this->getVantivConfig()->getValue('merchant_id'),
                'order_id'     => $id,
                'litle_txn_id' => $parser->getLitleTxnId(),
                'success_flag' => $success,
                'request'      => $request,
                'response'     => $response,
            ]);
        }
    }

    /**
     * Run AuthReversal tests.
     */
    private function executeAuthReversalTests()
    {
        /*
         * Test PayPal AuthReversal transactions.
         */
        $renderer = $this->getAuthReversalRenderer();

        foreach ($this->getAuthReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createAuthReversalParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', AuthReversal, Dataset "' . $id . '"',
                'store_id'     => $this->storeManager->getStore()->getId(),
                'merchant_id'  => $this->getVantivConfig()->getValue('merchant_id'),
                'order_id'     => $id,
                'litle_txn_id' => $parser->getLitleTxnId(),
                'success_flag' => $success,
                'request'      => $request,
                'response'     => $response,
            ]);
        }
    }

    /**
     * Run Sale tests.
     */
    private function executeSaleTests()
    {
        /*
         * Test PayPal Sale transactions.
         */
        $renderer = $this->getSaleRenderer();

        foreach ($this->getSaleRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createSaleParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Sale, Dataset "' . $id . '"',
                'store_id'     => $this->storeManager->getStore()->getId(),
                'merchant_id'  => $this->getVantivConfig()->getValue('merchant_id'),
                'order_id'     => $id,
                'litle_txn_id' => $parser->getLitleTxnId(),
                'success_flag' => $success,
                'request'      => $request,
                'response'     => $response,
            ]);
        }
    }

    /**
     * Run Credit tests.
     */
    private function executeCreditTests()
    {
        /*
         * Test PayPal Credit transactions.
         */
        $renderer = $this->getCreditRenderer();

        foreach ($this->getCreditRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createCreditParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Credit, Dataset "' . $id . '"',
                'store_id'     => $this->storeManager->getStore()->getId(),
                'merchant_id'  => $this->getVantivConfig()->getValue('merchant_id'),
                'order_id'     => $id,
                'litle_txn_id' => $parser->getLitleTxnId(),
                'success_flag' => $success,
                'request'      => $request,
                'response'     => $response,
            ]);
        }
    }

    /**
     * Get authorization renderer instance.
     *
     * @return PaypalAuthorizationRenderer
     */
    private function getAuthorizationRenderer()
    {
        return $this->paypalAuthorizationRenderer;
    }

    /**
     * Get capture renderer instance.
     *
     * @return PaypalCaptureRenderer
     */
    private function getCaptureRenderer()
    {
        return $this->paypalCaptureRenderer;
    }

    /**
     * Get authReversal renderer instance.
     *
     * @return PaypalAuthReversalRenderer
     */
    private function getAuthReversalRenderer()
    {
        return $this->paypalAuthReversalRenderer;
    }

    /**
     * Get sale renderer instance.
     *
     * @return PaypalSaleRenderer
     */
    private function getSaleRenderer()
    {
        return $this->paypalSaleRenderer;
    }

    /**
     * Get credit renderer instance.
     *
     * @return PaypalCreditRenderer
     */
    private function getCreditRenderer()
    {
        return $this->paypalCreditRenderer;
    }

    /**
     * Initialize authorization parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Paypal\Parser\PaypalAuthorizeResponseParser
     */
    private function createAuthorizationParser($xml)
    {
        return $this->paypalAuthorizeResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize capture parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Paypal\Parser\PaypalCaptureResponseParser
     */
    private function createCaptureParser($xml)
    {
        return $this->paypalCaptureResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize authReversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Paypal\Parser\PaypalVoidResponseParser
     */
    private function createAuthReversalParser($xml)
    {
        return $this->paypalAuthReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize sale parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Paypal\Parser\PaypalSaleResponseParser
     */
    private function createSaleParser($xml)
    {
        return $this->paypalSaleResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize credit parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Paypal\Parser\PaypalCreditResponseParser
     */
    private function createCreditParser($xml)
    {
        return $this->paypalCreditResponseParserFactory->create(['xml' => $xml]);
    }

    /*
     * ======================
     * Test data begins here.
     * ======================
     */

    /**
     * Get PayPal Authorization data.
     *
     * @return array
     */
    private function getAuthorizationRequestData()
    {
        $data = [
            '1' => [
                'orderId'           => '1',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'John & Mary Smith',
                    'addressLine1'      => '1 Main St.',
                    'city'              => 'Burlington',
                    'state'             => 'MA',
                    'zip'               => '01803-3747',
                    'country'           => 'US',
                ],
                'payerId'           => 'NLHGHUKKGYPB6',
                'transactionId'     => 'O-1JE58096JE278301X',
            ],
            '2' => [
                'orderId'           => '1',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'John & Mary Smith',
                    'addressLine1'      => '1 Main St.',
                    'city'              => 'Burlington',
                    'state'             => 'MA',
                    'zip'               => '01803-3747',
                    'country'           => 'US',
                ],
                'payerId'           => 'NLHGHUKKGYPB6',
                'transactionId'     => 'O-1JE58096JE278301X',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal Capture data.
     *
     * @return array
     */
    private function getCaptureRequestData()
    {
        $data = [
            '1A' => [
                'litleTxnId' => $this->getCachedResponseValueById('1', 'litleTxnId'),
                'amount' => '10100',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal authReversal data.
     *
     * @return array
     */
    private function getAuthReversalRequestData()
    {
        $data = [
            '2A' => [
                'litleTxnId' => $this->getCachedResponseValueById('2', 'litleTxnId'),
                'amount' => '10100',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal Sale data.
     *
     * @return array
     */
    private function getSaleRequestData()
    {
        $data = [
            '1_sale' => [
                'orderId'           => '1',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'John & Mary Smith',
                    'addressLine1'      => '1 Main St.',
                    'city'              => 'Burlington',
                    'state'             => 'MA',
                    'zip'               => '01803-3747',
                    'country'           => 'US',
                ],
                'payerId'           => 'NLHGHUKKGYPB6',
                'transactionId'     => 'O-1JE58096JE278301X',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal credit data.
     *
     * @return array
     */
    private function getCreditRequestData()
    {
        $data = [
            '3' => [
                'litleTxnId' => $this->getCachedResponseValueById('1_sale', 'litleTxnId'),
                'amount' => '10100',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal Authorization result test data.
     *
     * @return array
     */
    private function getAuthorizationResponseData()
    {
        $data = [
            '1' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
            '2' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal Capture result test data.
     *
     * @return array
     */
    private function getCaptureResponseData()
    {
        $data = [
            '1A' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal authReversal result test data.
     *
     * @return array
     */
    private function getAuthReversalResponseData()
    {
        $data = [
            '2A' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal Sale result test data.
     *
     * @return array
     */
    private function getSaleResponseData()
    {
        $data = [
            '1_sale' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get PayPal Credit result test data.
     *
     * @return array
     */
    private function getCreditResponseData()
    {
        $data = [
            '3' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get specific test response data.
     *
     * @param string $id
     * @return array
     */
    private function getResponseDataById($id)
    {
        $data = $this->getAuthorizationResponseData()
            + $this->getCaptureResponseData()
            + $this->getAuthReversalResponseData()
            + $this->getSaleResponseData()
            + $this->getCreditResponseData();

        return $data[$id];
    }

    /**
     * Save cahced response data.
     *
     * @param string $id
     * @param array $data
     * @return void
     */
    private function setCachedResponseData($id, array $data)
    {
        $this->cachedResponseData[$id] = $data;
    }

    /**
     * Get cached response data.
     *
     * @param string $id
     * @return array
     */
    private function getCachedResponseDataById($id)
    {
        return array_key_exists($id, $this->cachedResponseData)
            ? $this->cachedResponseData[$id]
            : [];
    }

    /**
     * Get cached response value.
     *
     * @param string $id
     * @param string $key
     * @return null|string
     */
    private function getCachedResponseValueById($id, $key)
    {
        $data = $this->getCachedResponseDataById($id);

        $value = array_key_exists($key, $data) ? $data[$key] : null;

        return $value;
    }
}
