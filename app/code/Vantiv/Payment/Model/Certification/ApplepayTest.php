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

use Vantiv\Payment\Gateway\Common\Renderer\CcAuthorizationRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\RegisterTokenRenderer;

use Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\RegisterTokenResponseParserFactory;

/**
 * Certification test model
 */
class ApplepayTest extends AbstractTest
{
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
     * @var CcAuthorizationRenderer
     */
    private $ccAuthorizationRenderer = null;

    /**
     * @var RegisterTokenRenderer
     */
    private $registerTokenRenderer = null;

    /**
     * @var AuthorizationResponseParserFactory
     */
    private $authorizationResponseParserFactory = null;

    /**
     * @var RegisterTokenResponseParserFactory
     */
    private $registerTokenResponseParserFactory = null;

    /**
     * Constructor
     *
     * @param Config $config
     * @param TestResponseParserFactory $parserFactory
     * @param Command $command
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param VantivConfig $vantivConfig
     * @param CcAuthorizationRenderer $ccAuthorizationRenderer
     * @param RegisterTokenRenderer $registerTokenRenderer
     * @param AuthorizationResponseParserFactory $authorizationResponseParserFactory
     * @param RegisterTokenResponseParserFactory $registerTokenResponseParserFactory
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        Command $command,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        CcAuthorizationRenderer $ccAuthorizationRenderer,
        RegisterTokenRenderer $registerTokenRenderer,
        AuthorizationResponseParserFactory $authorizationResponseParserFactory,
        RegisterTokenResponseParserFactory $registerTokenResponseParserFactory,
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
        $this->ccAuthorizationRenderer = $ccAuthorizationRenderer;
        $this->registerTokenRenderer = $registerTokenRenderer;
        $this->authorizationResponseParserFactory = $authorizationResponseParserFactory;
        $this->registerTokenResponseParserFactory = $registerTokenResponseParserFactory;
    }

    /**
     * Get path prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return 'payment/vantiv_applepay/';
    }

    /**
     * Get feature id
     *
     * @return string
     */
    public function getId()
    {
        return 'vantiv_applepay';
    }

    /**
     * Run ApplePay Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeAuthorizationTests();
        $this->executeRegisterTokenTests();
    }

    /**
     * Run Authorization tests.
     */
    private function executeAuthorizationTests()
    {
        /*
         * Test ApplePay Authorization transactions.
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
     * Run RegisterToken tests.
     */
    private function executeRegisterTokenTests()
    {
        /*
         * Test AndroidPay RegisterToken transactions.
         */
        $renderer = $this->getRegisterTokenRenderer();

        foreach ($this->getRegisterTokenRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createRegisterTokenParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Register Token, Dataset "' . $id . '"',
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
     * Get renderer instance.
     *
     * @return CcAuthorizationRenderer
     */
    private function getAuthorizationRenderer()
    {
        return $this->ccAuthorizationRenderer;
    }

    /**
     * Get renderer instance.
     *
     * @return RegisterTokenRenderer
     */
    private function getRegisterTokenRenderer()
    {
        return $this->registerTokenRenderer;
    }

    /**
     * Initialize parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParser
     */
    private function createAuthorizationParser($xml)
    {
        return $this->authorizationResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\RegisterTokenResponseParser
     */
    private function createRegisterTokenParser($xml)
    {
        return $this->registerTokenResponseParserFactory->create(['xml' => $xml]);
    }

    /*
     * ======================
     * Test data begins here.
     * ======================
     */

    /**
     * Get Apple Pay Authorization data.
     *
     * @return array
     */
    private function getAuthorizationRequestData()
    {
        $data = [
            'applepay_1' => [
                'orderId'     => 'applepay_1',
                'amount'      => '40000',
                'orderSource' => 'applepay',
                'paypageRegistrationId' =>
                    'cVMyVklobTdLcmFUTHoySXRKQVJ3RktEdHV3MnhWeXl0L1FxMDRvSDZMQW5ybkdwZ204WHdlWkcxQXJVVVdKcA1paQ==',
            ],
        ];

        return $data;
    }

    /**
     * Get Apple Pay RegisterToken data.
     *
     * @return array
     */
    private function getRegisterTokenRequestData()
    {
        $data = [
            'applepay_2' => [
                'orderId'     => 'applepay_2',
                'paypageRegistrationId' =>
                    'cVMyVklobTdLcmFUTHoySXRKQVJ3RktEdHV3MnhWeXl0L1FxMDRvSDZMQW5ybkdwZ204WHdlWkcxQXJVVVdKcA1paQ==',
            ],
        ];

        return $data;
    }

    /**
     * Get Apple Pay Authorization result test data.
     *
     * @return array
     */
    private function getAuthorizationResponseData()
    {
        $data = [
            'applepay_1' => [
                'response' => '877',
                'message'  => 'Invalid paypage registration id',
            ],
        ];

        return $data;
    }

    /**
     * Get Apple Pay RegisterToken result test data.
     *
     * @return array
     */
    private function getRegisterTokenResponseData()
    {
        $data = [
            'applepay_2' => [
                'response' => '877',
                'message'  => 'Invalid paypage registration id',
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
        $data = $this->getAuthorizationResponseData() + $this->getRegisterTokenResponseData();

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
