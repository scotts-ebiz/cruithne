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
use Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParserFactory;

/**
 * Certification test model
 */
class AdvancedFraudTest extends AbstractTest
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
    private $authorizationRenderer = null;

    /**
     * @var AuthorizationResponseParserFactory
     */
    private $authorizationResponseParserFactory = null;

    /**
     * Constructor
     *
     * @param Config $config
     * @param TestResponseParserFactory $parserFactory
     * @param Command $command
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param VantivConfig $vantivConfig
     * @param CcAuthorizationRenderer $authorizationRenderer
     * @param AuthorizationResponseParserFactory $authorizationResponseParserFactory
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        Command $command,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        CcAuthorizationRenderer $authorizationRenderer,
        AuthorizationResponseParserFactory $authorizationResponseParserFactory,
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

        $this->authorizationRenderer = $authorizationRenderer;
        $this->authorizationResponseParserFactory = $authorizationResponseParserFactory;
    }

    /**
     * Get test name
     *
     * @return string
     */
    public function getName()
    {
        return 'Advanced Fraud Toolkit';
    }

    /**
     * Get path prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return 'payment/vantiv_cc/';
    }

    /**
     * Get feature id
     *
     * @return string
     */
    public function getId()
    {
        return 'vantiv_cc_fraud';
    }

    /**
     * Get "active" flag
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->getConfig()->getValue('payment/vantiv_cc/advanced_fraud_is_active');
    }

    /**
     * Run Advanced Fraud Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
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
                'name'         => $this->getName() . ', Dataset "' . $id . '"',
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
     * @return \Vantiv\Payment\Gateway\Common\Renderer\CcAuthorizationRenderer
     */
    private function getAuthorizationRenderer()
    {
        return $this->authorizationRenderer;
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

    /*
     * ======================
     * Test data begins here.
     * ======================
     */

    /**
     * Get advanced fraud authorization data.
     *
     * @return array
     */
    private function getAuthorizationRequestData()
    {
        $data = [
            'tmx_pass_order_id' => [
                'orderId'               => 'tmx_pass_order_id',
                'amount'                => '150',
                'type'                  => 'VI',
                'number'                => '4111111111111111',
                'expDate'               => '1230',
                'threatMetrixSessionId' => $this->getVantivConfig()->getValue('threatmetrix_sessionprefix')
                                               . 'A980A93LP2O3-KNP0050',
            ],
            'tmx_review_order_id' => [
                'orderId'               => 'tmx_review_order_id',
                'amount'                => '150',
                'type'                  => 'VI',
                'number'                => '4111111111111111',
                'expDate'               => '1230',
                'threatMetrixSessionId' => $this->getVantivConfig()->getValue('threatmetrix_sessionprefix')
                                               . 'A0S9D8F7G6H5J4-KMR-020',
            ],
            'tmx_fail_order_id' => [
                'orderId'     => 'tmx_fail_order_id',
                'amount'                => '150',
                'type'                  => 'VI',
                'number'                => '4111111111111111',
                'expDate'               => '1230',
                'threatMetrixSessionId' => $this->getVantivConfig()->getValue('threatmetrix_sessionprefix')
                                               . 'Q1W2E3R4T5Y6U7I8-KHF-100',
            ],
            'tmx_unavail_order_id' => [
                'orderId'     => 'tmx_unavail_order_id',
                'amount'                => '150',
                'type'                  => 'VI',
                'number'                => '4111111111111111',
                'expDate'               => '1230',
                'threatMetrixSessionId' => $this->getVantivConfig()->getValue('threatmetrix_sessionprefix')
                                               . 'Q1W2E3R4T5Y6U7I8-XLP0050',
            ],
        ];

        return $data;
    }

    /**
     * Get advanced fraud authorization result test data.
     *
     * @return array
     */
    private function getAuthorizationResponseData()
    {
        $data = [
            'tmx_pass_order_id' => [
                'response'              => '000',
                'message'               => 'Approved',
                'deviceReviewStatus'    => 'pass',
                'deviceReputationScore' => '50',
            ],
            'tmx_review_order_id' => [
                'response'              => '000',
                'message'               => 'Approved',
                'deviceReviewStatus'    => 'review',
                'deviceReputationScore' => '-20',
            ],
            'tmx_fail_order_id' => [
                'deviceReviewStatus'    => 'fail',
                'deviceReputationScore' => '-100',
            ],
            'tmx_unavail_order_id' => [
                'response'              => '000',
                'message'               => 'Approved',
                'deviceReviewStatus'    => 'unavailable',
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
        $data = $this->getAuthorizationResponseData();

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
