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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardSaleRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardCreditRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardRefundReversalRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardDepositReversalRenderer;

use Vantiv\Payment\Gateway\Cc\Parser\SaleResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\CreditResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardRefundReversalResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDepositReversalResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardSaleTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardSaleRenderer
     */
    private $giftCardSaleRenderer = null;

    /**
     * @var GiftCardCreditRenderer
     */
    private $giftCardCreditRenderer = null;

    /**
     * @var GiftCardRefundReversalRenderer
     */
    private $giftCardRefundReversalRenderer = null;

    /**
     * @var GiftCardDepositReversalRenderer
     */
    private $giftCardDepositReversalRenderer = null;

    /**
     * @var SaleResponseParserFactory
     */
    private $giftCardSaleResponseParserFactory = null;

    /**
     * @var CreditResponseParserFactory
     */
    private $giftCardCreditResponseParserFactory = null;

    /**
     * @var GiftCardRefundReversalResponseParserFactory
     */
    private $giftCardRefundReversalResponseParserFactory = null;

    /**
     * @var GiftCardDepositReversalResponseParserFactory
     */
    private $giftCardDepositReversalResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardSaleRenderer $giftCardSaleRenderer
     * @param GiftCardCreditRenderer $giftCardCreditRenderer
     * @param GiftCardRefundReversalRenderer $giftCardRefundReversalRenderer
     * @param GiftCardDepositReversalRenderer $giftCardDepositReversalRenderer
     * @param SaleResponseParserFactory $giftCardSaleResponseParserFactory
     * @param CreditResponseParserFactory $giftCardCreditResponseParserFactory
     * @param GiftCardRefundReversalResponseParserFactory $giftCardRefundReversalResponseParserFactory
     * @param GiftCardDepositReversalResponseParserFactory $giftCardDepositReversalResponseParserFactory
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
        GiftCardSaleRenderer $giftCardSaleRenderer,
        GiftCardCreditRenderer $giftCardCreditRenderer,
        GiftCardRefundReversalRenderer $giftCardRefundReversalRenderer,
        GiftCardDepositReversalRenderer $giftCardDepositReversalRenderer,
        SaleResponseParserFactory $giftCardSaleResponseParserFactory,
        CreditResponseParserFactory $giftCardCreditResponseParserFactory,
        GiftCardRefundReversalResponseParserFactory $giftCardRefundReversalResponseParserFactory,
        GiftCardDepositReversalResponseParserFactory $giftCardDepositReversalResponseParserFactory,
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

        $this->giftCardSaleRenderer = $giftCardSaleRenderer;
        $this->giftCardCreditRenderer = $giftCardCreditRenderer;
        $this->giftCardRefundReversalRenderer = $giftCardRefundReversalRenderer;
        $this->giftCardDepositReversalRenderer = $giftCardDepositReversalRenderer;
        $this->giftCardSaleResponseParserFactory = $giftCardSaleResponseParserFactory;
        $this->giftCardCreditResponseParserFactory = $giftCardCreditResponseParserFactory;
        $this->giftCardRefundReversalResponseParserFactory = $giftCardRefundReversalResponseParserFactory;
        $this->giftCardDepositReversalResponseParserFactory = $giftCardDepositReversalResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeSaleTests();
        $this->executeCreditTests();
        $this->executeRefundReversalTests();
        $this->executeDepositReversalTests();
    }

    /**
     * Run Sale tests
     *
     * @return void
     */
    private function executeSaleTests()
    {
        /*
         * Test Gift Card Sale transactions.
         */
        $renderer = $this->getGiftCardSaleRenderer();

        foreach ($this->getGiftCardSaleRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardSaleResponseParser($response);

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
     * Run RefundReversal tests
     *
     * @return void
     */
    private function executeRefundReversalTests()
    {
        /*
         * Test Gift Card RefundReversal transactions.
         */
        $renderer = $this->getGiftCardRefundReversalRenderer();

        foreach ($this->getGiftCardRefundReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardRefundReversalResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', RefundReversal, Dataset "' . $id . '"',
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
     * Run DepositReversal tests
     *
     * @return void
     */
    private function executeDepositReversalTests()
    {
        /*
         * Test Gift Card DepositReversal transactions.
         */
        $renderer = $this->getGiftCardDepositReversalRenderer();

        foreach ($this->getGiftCardDepositReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardDepositReversalResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', DepositReversal, Dataset "' . $id . '"',
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
     * Get Sale Renderer instance.
     *
     * @return GiftCardSaleRenderer
     */
    private function getGiftCardSaleRenderer()
    {
        return $this->giftCardSaleRenderer;
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
     * Get RefundReversal Renderer instance.
     *
     * @return GiftCardRefundReversalRenderer
     */
    private function getGiftCardRefundReversalRenderer()
    {
        return $this->giftCardRefundReversalRenderer;
    }

    /**
     * Get DepositReversal Renderer instance.
     *
     * @return GiftCardDepositReversalRenderer
     */
    private function getGiftCardDepositReversalRenderer()
    {
        return $this->giftCardDepositReversalRenderer;
    }

    /**
     * Initialize Gift Card Sale parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\SaleResponseParser
     */
    private function createGiftCardSaleResponseParser($xml)
    {
        return $this->giftCardSaleResponseParserFactory->create(['xml' => $xml]);
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
     * Initialize Gift Card RefundReversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardRefundReversalResponseParser
     */
    private function createGiftCardRefundReversalResponseParser($xml)
    {
        return $this->giftCardRefundReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card DepositReversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDepositReversalResponseParser
     */
    private function createGiftCardDepositReversalResponseParser($xml)
    {
        return $this->giftCardDepositReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card Sale data.
     *
     * @return array
     */
    private function getGiftCardSaleRequestData()
    {
        $data = [
            'GC9' => [
                'orderId'           => 'GC9',
                'amount'            => '2000',
                'type'              => 'GC',
                'number'            => '6035716431320000005',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
            ],
            'GC10' => [
                'orderId'           => 'GC10',
                'amount'            => '2000',
                'type'              => 'GC',
                'number'            => '6035716431320000005',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
            ],
            'GC19' => [
                'orderId'           => 'GC19',
                'amount'            => '1000',
                'type'              => 'GC',
                'number'            => '6035716490000000008',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
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
            'GC10A' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC10', 'litleTxnId'),
            ],
            'GC19A' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC19', 'litleTxnId'),
                'amount'     => '10000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card RefundReversal data.
     *
     * @return array
     */
    private function getGiftCardRefundReversalRequestData()
    {
        $data = [
            'GC10B' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC10A', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card DepositReversal data.
     *
     * @return array
     */
    private function getGiftCardDepositReversalRequestData()
    {
        $data = [
            'GC9A' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC9', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Sale result test data.
     *
     * @return array
     */
    private function getGiftCardSaleResponseData()
    {
        $data = [
            'GC9' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'cardValidationResult' => 'M',
                'availableBalance'     => '8000',
            ],
            'GC10' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'cardValidationResult' => 'M',
                'availableBalance'     => '8000',
            ],
            'GC19' => [
                'response'             => '000',
                'message'              => 'Approved',
                'authCode'             => '11111',
                'cardValidationResult' => 'M',
                'availableBalance'     => '1500',
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
            'GC10A' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '10000',
            ],
            'GC19A' => [
                'response'             => '304',
                'message'              => 'Lost/StolenCard',
                'availableBalance'     => '10000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card RefundReversal result test data.
     *
     * @return array
     */
    private function getGiftCardRefundReversalResponseData()
    {
        $data = [
            'GC10B' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '8000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card DepositReversal result test data.
     *
     * @return array
     */
    private function getGiftCardDepositReversalResponseData()
    {
        $data = [
            'GC9A' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '10000',
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
        $data = $this->getGiftCardSaleResponseData()
            + $this->getGiftCardCreditResponseData()
            + $this->getGiftCardRefundReversalResponseData()
            + $this->getGiftCardDepositReversalResponseData();

        return $data[$id];
    }
}
