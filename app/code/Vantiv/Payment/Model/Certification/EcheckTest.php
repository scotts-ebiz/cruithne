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

use Vantiv\Payment\Gateway\Common\Renderer\EcheckVerificationRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\EcheckSaleRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\EcheckCreditRenderer;

use Vantiv\Payment\Gateway\Echeck\Parser\EcheckVerificationResponseParserFactory;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckSalesResponseParserFactory;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckCreditResponseParserFactory;

/**
 * Certification test model
 */
class EcheckTest extends AbstractTest
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
     * @var EcheckVerificationRenderer
     */
    private $echeckVerificationRenderer = null;

    /**
     * @var EcheckSaleRenderer
     */
    private $echeckSaleRenderer = null;

    /**
     * @var EcheckCreditRenderer
     */
    private $echeckCreditRenderer = null;

    /**
     * @var EcheckVerificationResponseParserFactory
     */
    private $echeckVerificationResponseParserFactory = null;

    /**
     * @var EcheckSalesResponseParserFactory
     */
    private $echeckSalesResponseParserFactory = null;

    /**
     * @var EcheckCreditResponseParserFactory
     */
    private $echeckCreditResponseParserFactory = null;

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
        EcheckVerificationRenderer $echeckVerificationRenderer,
        EcheckSaleRenderer $echeckSaleRenderer,
        EcheckCreditRenderer $echeckCreditRenderer,
        EcheckVerificationResponseParserFactory $echeckVerificationResponseParserFactory,
        EcheckSalesResponseParserFactory $echeckSalesResponseParserFactory,
        EcheckCreditResponseParserFactory $echeckCreditResponseParserFactory,
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

        $this->echeckVerificationRenderer = $echeckVerificationRenderer;
        $this->echeckSaleRenderer = $echeckSaleRenderer;
        $this->echeckCreditRenderer = $echeckCreditRenderer;

        $this->echeckVerificationResponseParserFactory = $echeckVerificationResponseParserFactory;
        $this->echeckSalesResponseParserFactory = $echeckSalesResponseParserFactory;
        $this->echeckCreditResponseParserFactory = $echeckCreditResponseParserFactory;
    }

    /**
     * Get path prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return 'payment/vantiv_echeck/';
    }

    /**
     * Get feature id
     *
     * @return string
     */
    public function getId()
    {
        return 'vantiv_echeck';
    }

    /**
     * Run Credit Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeVerificationTests();
        $this->executeSaleTests();
        $this->executeCreditTests();
    }

    /**
     * Run Verification tests.
     */
    private function executeVerificationTests()
    {
        /*
         * Test eCheck Verification transactions.
         */
        $renderer = $this->getVerificationRenderer();

        foreach ($this->getVerificationRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createVerificationParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Verification, Dataset "' . $id . '"',
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
     * Get renderer instance.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\EcheckVerificationRenderer
     */
    private function getVerificationRenderer()
    {
        return $this->echeckVerificationRenderer;
    }

    /**
     * Get renderer instance.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\EcheckSaleRenderer
     */
    private function getSaleRenderer()
    {
        return $this->echeckSaleRenderer;
    }

    /**
     * Get renderer instance.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\EcheckCreditRenderer
     */
    private function getCreditRenderer()
    {
        return $this->echeckCreditRenderer;
    }

    /**
     * Initialize parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Echeck\Parser\EcheckVerificationResponseParser
     */
    private function createVerificationParser($xml)
    {
        return $this->echeckVerificationResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Echeck\Parser\EcheckSalesResponseParser
     */
    private function createSaleParser($xml)
    {
        return $this->echeckSalesResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Echeck\Parser\EcheckCreditResponseParser
     */
    private function createCreditParser($xml)
    {
        return $this->echeckCreditResponseParserFactory->create(['xml' => $xml]);
    }

    /*
     * ======================
     * Test data begins here.
     * ======================
     */

    /**
     * Get eCheck Verification data.
     *
     * @return array
     */
    private function getVerificationRequestData()
    {
        $data = [
            '37' => [
                'orderId'     => '37',
                'amount'      => '3001',
                'orderSource' => 'telephone',
                'billToAddress' => [
                    'firstName'   => 'Tom',
                    'lastName'    => 'Black',
                ],
                'accType'     => 'Checking',
                'accNum'      => '10@BC99999',
                'routingNum'  => '053100300',
            ],
            '38' => [
                'orderId'     => '38',
                'amount'      => '3002',
                'orderSource' => 'telephone',
                'billToAddress' => [
                    'firstName'   => 'John',
                    'lastName'    => 'Smith',
                ],
                'accType'     => 'Checking',
                'accNum'      => '1099999999',
                'routingNum'  => '011075150',
            ],
            '39' => [
                'orderId'     => '39',
                'amount'      => '3003',
                'orderSource' => 'telephone',
                'billToAddress' => [
                    'firstName'   => 'Robert',
                    'lastName'    => 'Jones',
                    'companyName' => 'Good Goods Inc',
                ],
                'accType'     => 'Corporate',
                'accNum'      => '3099999999',
                'routingNum'  => '053100300',
            ],
            '40' => [
                'orderId'     => '40',
                'amount'      => '3004',
                'orderSource' => 'telephone',
                'billToAddress' => [
                    'firstName'   => 'Peter',
                    'lastName'    => 'Green',
                    'companyName' => 'Green Co',
                ],
                'accType'     => 'Corporate',
                'accNum'      => '8099999999',
                'routingNum'  => '011075150',
            ],
        ];

        return $data;
    }

    /**
     * Get eCheck test request data.
     *
     * @return array
     */
    private function getSaleRequestData()
    {
        $data = [
            '41' => [
                'amount'        => '2008',
                'orderSource'   => 'telephone',
                'billToAddress' => [
                    'firstName'     => 'Mike',
                    'middleInitial' => 'J',
                    'lastName'      => 'Hammer',
                ],
                'accType'       => 'Checking',
                'accNum'        => '10@BC99999',
                'routingNum'    => '053100300',
            ],
            '42' => [
                'amount'        => '2004',
                'orderSource'   => 'telephone',
                'billToAddress' => [
                    'firstName'     => 'Tom',
                    'lastName'      => 'Black',
                ],
                'accType'       => 'Checking',
                'accNum'        => '4099999992',
                'routingNum'    => '011075150',
            ],
            '43' => [
                'amount'        => '2007',
                'orderSource'   => 'telephone',
                'billToAddress' => [
                    'firstName'     => 'Peter',
                    'lastName'      => 'Green',
                    'companyName'   => 'Green Co',
                ],
                'accType'       => 'Corporate',
                'accNum'        => '6099999992',
                'routingNum'    => '011075150',
            ],
            '44' => [
                'amount'        => '2009',
                'orderSource'   => 'telephone',
                'billToAddress' => [
                    'firstName'     => 'Peter',
                    'lastName'      => 'Green',
                    'companyName'   => 'Green Co',
                ],
                'accType'       => 'Corporate',
                'accNum'        => '9099999992',
                'routingNum'    => '053133052',
            ],
        ];

        return $data;
    }

    /**
     * Get eCheck Credit request data.
     *
     * @return array
     */
    private function getCreditRequestData()
    {
        $data = [
            '48' => [
                'litleTxnId' => $this->getCachedResponseValueById('43', 'litleTxnId'),
            ],
            '49' => [
                'litleTxnId' => '2',
            ],
        ];

        return $data;
    }

    /**
     * Get eCheck Verification result test data.
     *
     * @return array
     */
    private function getVerificationResponseData()
    {
        $data = [
            '37' => [
                'response' => '301',
                'message'  => 'Invalid Account Number',
            ],
            '38' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '39' => [
                'response' => '950',
                'message'  => 'Decline - Negative Information on File',
            ],
            '40' => [
                'response' => '951',
                'message'  => 'Absolute Decline',
            ],
        ];

        return $data;
    }

    /**
     * Get eCheck Sale reponse data.
     *
     * @return array
     */
    private function getSaleResponseData()
    {
        $data = [
            '41' => [
                'response' => '301',
                'message'  => 'Invalid Account Number',
            ],
            '42' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '43' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '44' => [
                'response' => '900',
                'message'  => 'Invalid Bank Routing Number',
            ],
        ];

        return $data;
    }

    /**
     * Get eCheck Credit reponse data.
     *
     * @return array
     */
    private function getCreditResponseData()
    {
        $data = [
            '45' => [
                'response' => '301',
                'message'  => 'Invalid Account Number',
            ],
            '46' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '47' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '48' => [
                'response' => '000',
                'message'  => 'Approved',
            ],
            '49' => [
                'response' => '360',
                'message'  => 'No transaction found with specified litleTxnId',
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
        $data = $this->getVerificationResponseData()
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
