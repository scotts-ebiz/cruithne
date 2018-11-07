<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\GiftCard;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardAuthorizationRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardCaptureRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardCreditRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardAuthReversalRenderer;

use Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\CaptureResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\CreditResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardAuthorizationTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardAuthorizationRenderer
     */
    private $giftCardAuthorizationRenderer = null;

    /**
     * @var GiftCardCaptureRenderer
     */
    private $giftCardCaptureRenderer = null;

    /**
     * @var GiftCardCreditRenderer
     */
    private $giftCardCreditRenderer = null;

    /**
     * @var GiftCardAuthReversalRenderer
     */
    private $giftCardAuthReversalRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParserFactory
     */
    private $giftCardAuthorizationResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\Cc\Parser\CaptureResponseParserFactory
     */
    private $giftCardCaptureResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\Cc\Parser\CreditResponseParserFactory
     */
    private $giftCardCreditResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParserFactory
     */
    private $giftCardAuthReversalResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardAuthorizationRenderer $giftCardAuthorizationRenderer
     * @param GiftCardCaptureRenderer $giftCardCaptureRenderer
     * @param GiftCardCreditRenderer $giftCardCreditRenderer
     * @param GiftCardAuthReversalRenderer $giftCardAuthReversalRenderer
     * @param AuthorizationResponseParserFactory $giftCardAuthorizationResponseParserFactory
     * @param CaptureResponseParserFactory $giftCardCaptureResponseParserFactory
     * @param CreditResponseParserFactory $giftCardCreditResponseParserFactory
     * @param VoidResponseParserFactory $giftCardAuthReversalResponseParserFactory
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        Command $command,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        EntityFactoryInterface $entityFactory,
        GiftCardAuthorizationRenderer $giftCardAuthorizationRenderer,
        GiftCardCaptureRenderer $giftCardCaptureRenderer,
        GiftCardCreditRenderer $giftCardCreditRenderer,
        GiftCardAuthReversalRenderer $giftCardAuthReversalRenderer,
        AuthorizationResponseParserFactory $giftCardAuthorizationResponseParserFactory,
        CaptureResponseParserFactory $giftCardCaptureResponseParserFactory,
        CreditResponseParserFactory $giftCardCreditResponseParserFactory,
        VoidResponseParserFactory $giftCardAuthReversalResponseParserFactory,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $parserFactory,
            $command,
            $resultFactory,
            $storeManager,
            $vantivConfig,
            $entityFactory,
            $data
        );

        $this->giftCardAuthorizationRenderer = $giftCardAuthorizationRenderer;
        $this->giftCardCaptureRenderer = $giftCardCaptureRenderer;
        $this->giftCardCreditRenderer = $giftCardCreditRenderer;
        $this->giftCardAuthReversalRenderer = $giftCardAuthReversalRenderer;
        $this->giftCardAuthorizationResponseParserFactory = $giftCardAuthorizationResponseParserFactory;
        $this->giftCardCaptureResponseParserFactory = $giftCardCaptureResponseParserFactory;
        $this->giftCardCreditResponseParserFactory = $giftCardCreditResponseParserFactory;
        $this->giftCardAuthReversalResponseParserFactory = $giftCardAuthReversalResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeAuthorizationTests();
        $this->executeCaptureTests();
        $this->executeCreditTests();
        $this->executeAuthReversalTests();
    }

    /**
     * Run Authorization tests
     *
     * @return void
     */
    private function executeAuthorizationTests()
    {
        /*
         * Test Gift Card Authorization transactions.
         */
        $renderer = $this->getGiftCardAuthorizationRenderer();

        foreach ($this->getGiftCardAuthorizationRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardAuthorizationResponseParser($response);

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
     * Run Capture tests
     *
     * @return void
     */
    private function executeCaptureTests()
    {
        /*
         * Test Gift Card Capture transactions.
         */
        $renderer = $this->getGiftCardCaptureRenderer();

        foreach ($this->getGiftCardCaptureRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardCaptureResponseParser($response);

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
     * Run Credit tests
     *
     * @return void
     */
    private function executeCreditTests()
    {
        /*
         * Test Gift Card Credit transactions.
         */
        $renderer = $this->getGiftCardCreditRenderer();

        foreach ($this->getGiftCardCreditRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardCreditResponseParser($response);

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
     * Run AuthReversal tests
     *
     * @return void
     */
    private function executeAuthReversalTests()
    {
        /*
         * Test Gift Card AuthReversal transactions.
         */
        $renderer = $this->getGiftCardAuthReversalRenderer();

        foreach ($this->getGiftCardAuthReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardAuthReversalResponseParser($response);

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
     * Get Authorization Renderer instance.
     *
     * @return GiftCardAuthorizationRenderer
     */
    private function getGiftCardAuthorizationRenderer()
    {
        return $this->giftCardAuthorizationRenderer;
    }

    /**
     * Get Capture Renderer instance.
     *
     * @return GiftCardCaptureRenderer
     */
    private function getGiftCardCaptureRenderer()
    {
        return $this->giftCardCaptureRenderer;
    }

    /**
     * Get Credit Renderer instance.
     *
     * @return GiftCardCreditRenderer
     */
    private function getGiftCardCreditRenderer()
    {
        return $this->giftCardCreditRenderer;
    }

    /**
     * Get AuthReversal Renderer instance.
     *
     * @return GiftCardAuthReversalRenderer
     */
    private function getGiftCardAuthReversalRenderer()
    {
        return $this->giftCardAuthReversalRenderer;
    }

    /**
     * Initialize Gift Card Authorization parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParser
     */
    private function createGiftCardAuthorizationResponseParser($xml)
    {
        return $this->giftCardAuthorizationResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card Capture parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\CaptureResponseParser
     */
    private function createGiftCardCaptureResponseParser($xml)
    {
        return $this->giftCardCaptureResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card Credit parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\CreditResponseParser
     */
    private function createGiftCardCreditResponseParser($xml)
    {
        return $this->giftCardCreditResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card AuthReversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParser
     */
    private function createGiftCardAuthReversalResponseParser($xml)
    {
        return $this->giftCardAuthReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card Authorization data.
     *
     * @return array
     */
    private function getGiftCardAuthorizationRequestData()
    {
        $data = [
            'GC2' => [
                'orderId'           => 'GC2',
                'amount'            => '1500',
                'type'              => 'GC',
                'number'            => '6035716390000000000',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
            ],
            'GC8' => [
                'orderId'           => 'GC8',
                'amount'            => '2000',
                'type'              => 'GC',
                'number'            => '6035746100000000007',
                'expDate'           => '1215',
                'cardValidationNum' => '123',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Capture data.
     *
     * @return array
     */
    private function getGiftCardCaptureRequestData()
    {
        $data = [
            'GC2A' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC2', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Credit data.
     *
     * @return array
     */
    private function getGiftCardCreditRequestData()
    {
        $data = [
            'GC2B' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC2A', 'litleTxnId'),
                'amount'     => '500',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card AuthReversal data.
     *
     * @return array
     */
    private function getGiftCardAuthReversalRequestData()
    {
        $data = [
            'GC8A' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC8', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Authorization result test data.
     *
     * @return array
     */
    private function getGiftCardAuthorizationResponseData()
    {
        $data = [
            'GC2' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'cardValidationResult' => 'M',
                'availableBalance'     => '13500',
            ],
            'GC8' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'cardValidationResult' => 'M',
                'availableBalance'     => '4000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Capture result test data.
     *
     * @return array
     */
    private function getGiftCardCaptureResponseData()
    {
        $data = [
            'GC2A' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '13500',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Credit result test data.
     *
     * @return array
     */
    private function getGiftCardCreditResponseData()
    {
        $data = [
            'GC2B' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '6000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card AuthReversal result test data.
     *
     * @return array
     */
    private function getGiftCardAuthReversalResponseData()
    {
        $data = [
            'GC8A' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '15000',
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
        $data = $this->getGiftCardAuthorizationResponseData()
            + $this->getGiftCardCaptureResponseData()
            + $this->getGiftCardCreditResponseData()
            + $this->getGiftCardAuthReversalResponseData();

        return $data[$id];
    }
}
