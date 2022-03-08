<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Magento\Store\Model\StoreManagerInterface;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;

use Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\CaptureResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\CreditResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\SaleResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParserFactory;

use Vantiv\Payment\Gateway\Common\Renderer\CcAuthorizationRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\CcAuthReversalRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\CcCaptureRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\CcCreditRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\CcSaleRenderer;

/**
 * Certification test model
 */
class CreditCardTest extends AbstractTest
{
    /**
     * Cache of response data.
     *
     * @var array
     */
    private $cachedResponseData = [];

    /**
     * Certification Test Command
     *
     * @var Command
     */
    private $command;

    /**
     * @var CcAuthorizationRenderer
     */
    private $authorizationRenderer = null;

    /**
     * @var CcAuthReversalRenderer
     */
    private $authReversalRenderer = null;

    /**
     * @var CcCaptureRenderer
     */
    private $captureRenderer = null;

    /**
     * @var CcCreditRenderer
     */
    private $creditRenderer = null;

    /**
     * @var CcSaleRenderer
     */
    private $saleRenderer = null;

    /**
     * @var AuthorizationResponseParserFactory
     */
    private $authorizationResponseParserFactory = null;

    /**
     * @var CaptureResponseParserFactory
     */
    private $captureResponseParserFactory = null;

    /**
     * @var CreditResponseParserFactory
     */
    private $creditResponseParserFactory = null;

    /**
     * @var SaleResponseParserFactory
     */
    private $saleResponseParserFactory = null;

    /**
     * @var VoidResponseParserFactory
     */
    private $authReversalResponseParserFactory = null;

    /**
     * Constructor
     *
     * @param Config $config
     * @param TestResponseParserFactory $parserFactory
     * @param Command $command
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param VantivConfig $vantivConfig
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
        CcAuthReversalRenderer $authReversalRenderer,
        CcCaptureRenderer $captureRenderer,
        CcCreditRenderer $creditRenderer,
        CcSaleRenderer $saleRenderer,
        AuthorizationResponseParserFactory $authorizationResponseParserFactory,
        CaptureResponseParserFactory $captureResponseParserFactory,
        CreditResponseParserFactory $creditResponseParserFactory,
        SaleResponseParserFactory $saleResponseParserFactory,
        VoidResponseParserFactory $authReversalResponseParserFactory,
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
        $this->authReversalRenderer = $authReversalRenderer;
        $this->captureRenderer = $captureRenderer;
        $this->creditRenderer = $creditRenderer;
        $this->saleRenderer = $saleRenderer;

        $this->authorizationResponseParserFactory = $authorizationResponseParserFactory;
        $this->captureResponseParserFactory = $captureResponseParserFactory;
        $this->creditResponseParserFactory = $creditResponseParserFactory;
        $this->saleResponseParserFactory = $saleResponseParserFactory;
        $this->authReversalResponseParserFactory = $authReversalResponseParserFactory;
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
        return 'vantiv_cc';
    }

    /**
     * Run Credit Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeAuthorization();
        $this->executeCapture();
        $this->executeCredit();
        $this->executeAuthReversal();
        $this->executeSale();
    }

    /**
     * Run authorization tests.
     *
     * @return void
     */
    private function executeAuthorization()
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
     * Run sale tests.
     *
     * @return void
     */
    private function executeSale()
    {
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
     * Get authorization renderer.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\CcAuthorizationRenderer
     */
    private function getAuthorizationRenderer()
    {
        return $this->authorizationRenderer;
    }

    /**
     * Get sale renderer.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\CcSaleRenderer
     */
    private function getSaleRenderer()
    {
        return $this->saleRenderer;
    }

    /**
     * Get authorization response parser.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParser
     */
    private function createAuthorizationParser($xml)
    {
        return $this->authorizationResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get sale response parser.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\SaleResponseParser
     */
    private function createSaleParser($xml)
    {
        return $this->saleResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Run capture tests.
     *
     * @return void
     */
    private function executeCapture()
    {
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
     * Get capture renderer.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\CcCaptureRenderer
     */
    private function getCaptureRenderer()
    {
        return $this->captureRenderer;
    }

    /**
     * Create capture parser.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\CaptureResponseParser
     */
    private function createCaptureParser($xml)
    {
        return $this->captureResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Execute credit transaction tests.
     *
     * @return void
     */
    private function executeCredit()
    {
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
     * Get credit renderer.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\CcCreditRenderer
     */
    private function getCreditRenderer()
    {
        return $this->creditRenderer;
    }

    /**
     * Create credit parser.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\CreditResponseParser
     */
    private function createCreditParser($xml)
    {
        return $this->creditResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Execute authorization reversal tests.
     *
     * @return void
     */
    private function executeAuthReversal()
    {
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
     * Get authorization reversal renderer.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\CcAuthReversalRenderer
     */
    private function getAuthReversalRenderer()
    {
        return $this->authReversalRenderer;
    }

    /**
     * Get authorization reversal response parser.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParser
     */
    private function createAuthReversalParser($xml)
    {
        return $this->authReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get authorization request test data.
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
                'type'              => 'VI',
                'number'            => '4457010000000009',
                'expDate'           => '0121',
                'cardValidationNum' => '349',
            ],
            '3' => [
                'orderId'           => '3',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Eileen Jones',
                    'addressLine1'      => '3 Main St.',
                    'city'              => 'Bloomfield',
                    'state'             => 'CT',
                    'zip'               => '06002',
                    'country'           => 'US',
                ],
                'type'              => 'DI',
                'number'            => '6011010000000003',
                'expDate'           => '0321',
                'cardValidationNum' => '758',
            ],
            '4' => [
                'orderId'           => '4',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Bob Black',
                    'addressLine1'      => '4 Main St.',
                    'city'              => 'Laurel',
                    'state'             => 'MD',
                    'zip'               => '20708',
                    'country'           => 'US',
                ],
                'type'              => 'AX',
                'number'            => '375001014000009',
                'expDate'           => '0421',
            ],
            '6' => [
                'orderId'           => '6',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Joe Green',
                    'addressLine1'      => '6 Main St.',
                    'city'              => 'Derry',
                    'state'             => 'NH',
                    'zip'               => '03038',
                    'country'           => 'US',
                ],
                'type'              => 'VI',
                'number'            => '4457010100000008',
                'expDate'           => '0621',
                'cardValidationNum' => '992',
            ],
            '7' => [
                'orderId'           => '7',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Jane Murray',
                    'addressLine1'      => '7 Main St.',
                    'city'              => 'Amesbury',
                    'state'             => 'MA',
                    'zip'               => '01913',
                    'country'           => 'US',
                ],
                'type'              => 'MC',
                'number'            => '5112010100000002',
                'expDate'           => '0721',
                'cardValidationNum' => '251',
            ],
            '8' => [
                'orderId'           => '8',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Mark Johnson',
                    'addressLine1'      => '8 Main St.',
                    'city'              => 'Manchester',
                    'state'             => 'NH',
                    'zip'               => '03101',
                    'country'           => 'US',
                ],
                'type'              => 'DI',
                'number'            => '6011010100000002',
                'expDate'           => '0821',
                'cardValidationNum' => '184',
            ],
            '9' => [
                'orderId'           => '9',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'James Miller',
                    'addressLine1'      => '9 Main St.',
                    'city'              => 'Boston',
                    'state'             => 'MA',
                    'zip'               => '02134',
                    'country'           => 'US',
                ],
                'type'              => 'AX',
                'number'            => '375001010000003',
                'expDate'           => '0921',
                'cardValidationNum' => '0421',
            ],
            '14' => [
                'orderId'           => '14',
                'amount'            => '10100',
                'type'              => 'VI',
                'number'            => '4457010200000247',
                'expDate'           => '0821',
            ],
            '15' => [
                'orderId'           => '15',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5500000254444445',
                'expDate'           => '0321',
            ],
            '16' => [
                'orderId'           => '16',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5592106621450897',
                'expDate'           => '0321',
            ],
            '17' => [
                'orderId'           => '17',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5590409551104142',
                'expDate'           => '0321',
            ],
            '18' => [
                'orderId'           => '18',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5587755665222179',
                'expDate'           => '0321',
            ],
            '19' => [
                'orderId'           => '19',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5445840176552850',
                'expDate'           => '0321',
            ],
            '20' => [
                'orderId'           => '20',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5390016478904678',
                'expDate'           => '0321',
            ],
            '21' => [
                'orderId'           => '21',
                'amount'            => '10100',
                'type'              => 'VI',
                'number'            => '4100200300012009',
                'expDate'           => '0921',
            ],
            '22' => [
                'orderId'           => '22',
                'amount'            => '10100',
                'type'              => 'VI',
                'number'            => '4100200300013007',
                'expDate'           => '1121',
            ],
            '23' => [
                'orderId'           => '23',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5112010201000109',
                'expDate'           => '0421',
            ],
            '24' => [
                'orderId'           => '24',
                'amount'            => '10100',
                'type'              => 'MC',
                'number'            => '5112010202000108',
                'expDate'           => '0821',
            ],
            '25' => [
                'orderId'           => '25',
                'amount'            => '10100',
                'type'              => 'VI',
                'number'            => '4100200310000002',
                'expDate'           => '1121',
            ],
            '32' => [
                'orderId'           => '32',
                'amount'            => '10010',
                'billToAddress'     => [
                    'name'              => 'John Smith',
                    'addressLine1'      => '1 Main St.',
                    'city'              => 'Burlington',
                    'state'             => 'MA',
                    'zip'               => '01803-3747',
                    'country'           => 'US',
                ],
                'type'              => 'VI',
                'number'            => '4457010000000009',
                'expDate'           => '0121',
                'cardValidationNum' => '349',
            ],
            '34' => [
                'orderId'           => '34',
                'amount'            => '30030',
                'billToAddress'     => [
                    'name'              => 'Eileen Jones',
                    'addressLine1'      => '3 Main St.',
                    'city'              => 'Bloomfield',
                    'state'             => 'CT',
                    'zip'               => '06002',
                    'country'           => 'US',
                ],
                'type'              => 'DI',
                'number'            => '6011010000000003',
                'expDate'           => '0321',
                'cardValidationNum' => '758',
            ],
            '35' => [
                'orderId'           => '35',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Bob Black',
                    'addressLine1'      => '4 Main St.',
                    'city'              => 'Laurel',
                    'state'             => 'MD',
                    'zip'               => '20708',
                    'country'           => 'US',
                ],
                'type'              => 'AX',
                'number'            => '375001014000009',
                'expDate'           => '0421',
            ],
            '36' => [
                'orderId'           => '36',
                'amount'            => '20500',
                'type'              => 'AX',
                'number'            => '375000026600004',
                'expDate'           => '0521',
            ],
        ];

        return $data;
    }

    /**
     * Get sale request test data.
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
                'type'              => 'VI',
                'number'            => '4457010000000009',
                'expDate'           => '0121',
                'cardValidationNum' => '349',
            ],
            '3_sale' => [
                'orderId'           => '3',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Eileen Jones',
                    'addressLine1'      => '3 Main St.',
                    'city'              => 'Bloomfield',
                    'state'             => 'CT',
                    'zip'               => '06002',
                    'country'           => 'US',
                ],
                'type'              => 'DI',
                'number'            => '6011010000000003',
                'expDate'           => '0321',
                'cardValidationNum' => '758',
            ],
            '4_sale' => [
                'orderId'           => '4',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Bob Black',
                    'addressLine1'      => '4 Main St.',
                    'city'              => 'Laurel',
                    'state'             => 'MD',
                    'zip'               => '20708',
                    'country'           => 'US',
                ],
                'type'              => 'AX',
                'number'            => '375001014000009',
                'expDate'           => '0421',
            ],
            '6_sale' => [
                'orderId'           => '6',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Joe Green',
                    'addressLine1'      => '6 Main St.',
                    'city'              => 'Derry',
                    'state'             => 'NH',
                    'zip'               => '03038',
                    'country'           => 'US',
                ],
                'type'              => 'VI',
                'number'            => '4457010100000008',
                'expDate'           => '0621',
                'cardValidationNum' => '992',
            ],
            '7_sale' => [
                'orderId'           => '7',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Jane Murray',
                    'addressLine1'      => '7 Main St.',
                    'city'              => 'Amesbury',
                    'state'             => 'MA',
                    'zip'               => '01913',
                    'country'           => 'US',
                ],
                'type'              => 'MC',
                'number'            => '5112010100000002',
                'expDate'           => '0721',
                'cardValidationNum' => '251',
            ],
            '8_sale' => [
                'orderId'           => '8',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'Mark Johnson',
                    'addressLine1'      => '8 Main St.',
                    'city'              => 'Manchester',
                    'state'             => 'NH',
                    'zip'               => '03101',
                    'country'           => 'US',
                ],
                'type'              => 'DI',
                'number'            => '6011010100000002',
                'expDate'           => '0821',
                'cardValidationNum' => '184',
            ],
            '9_sale' => [
                'orderId'           => '9',
                'amount'            => '10100',
                'billToAddress'     => [
                    'name'              => 'James Miller',
                    'addressLine1'      => '9 Main St.',
                    'city'              => 'Boston',
                    'state'             => 'MA',
                    'zip'               => '02134',
                    'country'           => 'US',
                ],
                'type'              => 'AX',
                'number'            => '375001010000003',
                'expDate'           => '0921',
                'cardValidationNum' => '0421',
            ],
        ];

        return $data;
    }

    /**
     * Get authorization respones data.
     *
     * @return array
     */
    private function getAuthorizationResponseData()
    {
        $data = [
            '1' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'avsResult'            => '01',
                'cardValidationResult' => 'M',
            ],
            '3' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '33333',
                'avsResult'            => '10',
                'cardValidationResult' => 'M',
            ],
            '4' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => 'A12522',
                'avsResult'            => '02',
            ],
            '6' => [
                'response'             => '110',
                'message'              => 'Insufficient Funds',
                'avsResult'            => '34',
                'cardValidationResult' => 'P',
            ],
            '7' => [
                'response'             => '301',
                'message'              => 'Invalid Account Number',
                'avsResult'            => '34',
                'cardValidationResult' => 'N',
            ],
            '8' => [
                'response'             => '123',
                'message'              => 'Call Discover',
                'avsResult'            => '34',
                'cardValidationResult' => 'P',
            ],
            '9' => [
                'response'             => '303',
                'message'              => 'Pick Up Card',
                'avsResult'            => '34',
                'cardValidationResult' => 'P',
            ],
            '14' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '2000',
                'fundingSourceReloadable'       => 'NO',
                'fundingSourcePrepaidCardType'  => 'GIFT',
            ],
            '15' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '2000',
                'fundingSourceReloadable'       => 'YES',
                'fundingSourcePrepaidCardType'  => 'PAYROLL',
            ],
            '16' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '0',
                'fundingSourceReloadable'       => 'YES',
                'fundingSourcePrepaidCardType'  => 'PAYROLL',
            ],
            '17' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '6500',
                'fundingSourceReloadable'       => 'YES',
                'fundingSourcePrepaidCardType'  => 'PAYROLL',
            ],
            '18' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '12200',
                'fundingSourceReloadable'       => 'YES',
                'fundingSourcePrepaidCardType'  => 'PAYROLL',
            ],
            '19' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '20000',
                'fundingSourceReloadable'       => 'YES',
                'fundingSourcePrepaidCardType'  => 'PAYROLL',
            ],
            '20' => [
                'response'                      => '000',
                'message'                       => 'Approved',
                'fundingSourceType'             => 'PREPAID',
                'fundingSourceAvailableBalance' => '10050',
                'fundingSourceReloadable'       => 'YES',
                'fundingSourcePrepaidCardType'  => 'PAYROLL',
            ],
            '21' => [
                'response'             => '000',
                'message'              => 'Approved',
                'affluence'            => 'AFFLUENT',
            ],
            '22' => [
                'response'             => '000',
                'message'              => 'Approved',
                'affluence'            => 'MASS AFFLUENT',
            ],
            '23' => [
                'response'             => '000',
                'message'              => 'Approved',
                'affluence'            => 'AFFLUENT',
            ],
            '24' => [
                'response'             => '000',
                'message'              => 'Approved',
                'affluence'            => 'MASS AFFLUENT',
            ],
            '25' => [
                'response'             => '000',
                'message'              => 'Approved',
                'issuerCountry'        => 'BRA',
            ],
            '32' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'avsResult'            => '01',
                'cardValidationResult' => 'M',
            ],
            '34' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '33333',
                'avsResult'            => '10',
                'cardValidationResult' => 'M',
            ],
            '35' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => 'A12522',
                'avsResult'            => '02',
            ],
            '36' => [
                'response'             => '000',
                'message'              => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get sale respones data.
     *
     * @return array
     */
    private function getSaleResponseData()
    {
        $data = [
            '1_sale' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'avsResult'            => '01',
                'cardValidationResult' => 'M',
            ],
            '3_sale' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '33333',
                'avsResult'            => '10',
                'cardValidationResult' => 'M',
            ],
            '4_sale' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => 'A12522',
                'avsResult'            => '02',
            ],
            '6_sale' => [
                'response'             => '110',
                'message'              => 'Insufficient Funds',
                'avsResult'            => '34',
                'cardValidationResult' => 'P',
            ],
            '7_sale' => [
                'response'             => '301',
                'message'              => 'Invalid Account Number',
                'avsResult'            => '34',
                'cardValidationResult' => 'N',
            ],
            '8_sale' => [
                'response'             => '123',
                'message'              => 'Call Discover',
                'avsResult'            => '34',
                'cardValidationResult' => 'P',
            ],
            '9_sale' => [
                'response'             => '303',
                'message'              => 'Pick Up Card',
                'avsResult'            => '34',
                'cardValidationResult' => 'P',
            ],
        ];

        return $data;
    }

    /**
     * Get capture requests data.
     *
     * @return array
     */
    private function getCaptureRequestData()
    {
        $data = [
            '1A' => [
                'litleTxnId' => $this->getCachedResponseValueById('1', 'litleTxnId'),
            ],
            '3A' => [
                'litleTxnId' => $this->getCachedResponseValueById('3', 'litleTxnId'),
            ],
            '4A' => [
                'litleTxnId' => $this->getCachedResponseValueById('4', 'litleTxnId'),
            ],
            '32A' => [
                'amount' => '5050',
                'litleTxnId' => $this->getCachedResponseValueById('32', 'litleTxnId'),
            ],
            '35A' => [
                'amount' => '5050',
                'litleTxnId' => $this->getCachedResponseValueById('35', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get capture response data.
     *
     * @return array
     */
    private function getCaptureResponseData()
    {
        $data = [
            '1A' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '3A' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '4A' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '32A' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '35A' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get credit requests data.
     *
     * @return array
     */
    private function getCreditRequestData()
    {
        $data = [
            '1B' => [
                'litleTxnId' => $this->getCachedResponseValueById('1A', 'litleTxnId'),
            ],
            '3B' => [
                'litleTxnId' => $this->getCachedResponseValueById('3A', 'litleTxnId'),
            ],
            '4B' => [
                'litleTxnId' => $this->getCachedResponseValueById('4A', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get credit response data.
     *
     * @return array
     */
    private function getCreditResponseData()
    {
        $data = [
            '1B' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '3B' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '4B' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
        ];

        return $data;
    }

    /**
     * Get authorixation reversal request data.
     *
     * @return array
     */
    private function getAuthReversalRequestData()
    {
        $data = [
            '32B' => [
                'litleTxnId' => $this->getCachedResponseValueById('32', 'litleTxnId'),
            ],
            '34A' => [
                'litleTxnId' => $this->getCachedResponseValueById('34', 'litleTxnId'),
            ],
            '35B' => [
                'litleTxnId' => $this->getCachedResponseValueById('35', 'litleTxnId'),
                'amount'     => '5050',
            ],
            '36A' => [
                'litleTxnId' => $this->getCachedResponseValueById('36', 'litleTxnId'),
                'amount'     => '10000',
            ],
        ];

        return $data;
    }

    /**
     * Get authorization reversal response data.
     *
     * @return array
     */
    private function getAuthReversalResponseData()
    {
        $data = [
            '32B' => [
                'response' => '111',
                'message'  => 'Authorization amount has already been depleted',
            ],
            '34A' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '35B' => [
                'response' => '336',
                'message'  => 'Reversal amount does not match authorization amount',
            ],
            '36A' => [
                'response' => '336',
                'message'  => 'Reversal amount does not match authorization amount',
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
            + $this->getCreditResponseData()
            + $this->getSaleResponseData()
            + $this->getAuthReversalResponseData();

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
