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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardActivateRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardActivateReversalRenderer;

use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateReversalResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardActivateTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardActivateRenderer
     */
    private $giftCardActivateRenderer = null;

    /**
     * @var GiftCardActivateReversalRenderer
     */
    private $giftCardActivateReversalRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateResponseParserFactory
     */
    private $giftCardActivateResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateReversalResponseParserFactory
     */
    private $giftCardActivateReversalResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardActivateRenderer $giftCardActivateRenderer
     * @param GiftCardActivateReversalRenderer $giftCardActivateReversalRenderer
     * @param GiftCardActivateResponseParserFactory $giftCardActivateResponseParserFactory
     * @param GiftCardActivateReversalResponseParserFactory $giftCardActivateReversalResponseParserFactory
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
        GiftCardActivateRenderer $giftCardActivateRenderer,
        GiftCardActivateReversalRenderer $giftCardActivateReversalRenderer,
        GiftCardActivateResponseParserFactory $giftCardActivateResponseParserFactory,
        GiftCardActivateReversalResponseParserFactory $giftCardActivateReversalResponseParserFactory,
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

        $this->giftCardActivateRenderer = $giftCardActivateRenderer;
        $this->giftCardActivateReversalRenderer = $giftCardActivateReversalRenderer;
        $this->giftCardActivateResponseParserFactory = $giftCardActivateResponseParserFactory;
        $this->giftCardActivateReversalResponseParserFactory = $giftCardActivateReversalResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeActivateTests();
        $this->executeActivateReversalTests();
    }

    /**
     * Run Activate tests
     *
     * @return void
     */
    private function executeActivateTests()
    {
        /*
         * Test Gift Card Activate transactions.
         */
        $renderer = $this->getGiftCardActivateRenderer();

        foreach ($this->getGiftCardActivateRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardActivateResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Activate, Dataset "' . $id . '"',
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
     * Run Activate Reversal tests
     *
     * @return void
     */
    private function executeActivateReversalTests()
    {
        /*
         * Test Gift Card Activate Reversal transactions.
         */
        $renderer = $this->getGiftCardActivateReversalRenderer();

        foreach ($this->getGiftCardActivateReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardActivateReversalResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Activate Reversal Gift Card, Dataset "' . $id . '"',
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
     * Get Activate Renderer instance.
     *
     * @return GiftCardActivateRenderer
     */
    private function getGiftCardActivateRenderer()
    {
        return $this->giftCardActivateRenderer;
    }

    /**
     * Get Activate Reversal Renderer instance.
     *
     * @return GiftCardActivateReversalRenderer
     */
    private function getGiftCardActivateReversalRenderer()
    {
        return $this->giftCardActivateReversalRenderer;
    }

    /**
     * Initialize Gift Card Activate parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateResponseParser
     */
    private function createGiftCardActivateResponseParser($xml)
    {
        return $this->giftCardActivateResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card Activate Reversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateReversalResponseParser
     */
    private function createGiftCardActivateReversalResponseParser($xml)
    {
        return $this->giftCardActivateReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card Activate data.
     *
     * @return array
     */
    private function getGiftCardActivateRequestData()
    {
        $data = [
            'GC1' => [
                'orderId'           => 'GC1',
                'amount'            => '15000',
                'type'              => 'GC',
                'number'            => '6035716390000000000',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
            ],
            'GC7' => [
                'orderId'           => 'GC7',
                'amount'            => '8000',
                'type'              => 'GC',
                'number'            => '6035712132130000003',
                'expDate'           => '1215',
                'cardValidationNum' => '123',
            ],
            'GC14' => [
                'orderId'           => 'GC14',
                'amount'            => '15000',
                'type'              => 'GC',
                'number'            => '6035716396360000001',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
            ],
            'GC15' => [
                'orderId'           => 'GC15',
                'amount'            => '10000',
                'type'              => 'GC',
                'number'            => '6035716390000000000',
                'expDate'           => '1221',
                'cardValidationNum' => '123',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Activate Reversal data.
     *
     * @return array
     */
    private function getGiftCardActivateReversalRequestData()
    {
        $data = [
            'GC7R' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC7', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Activate result test data.
     *
     * @return array
     */
    private function getGiftCardActivateResponseData()
    {
        $data = [
            'GC1' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '15000',
            ],
            'GC7' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '8000',
            ],
            'GC14' => [
                'response'             => '301',
                'message'              => 'Invalid Account Number',
            ],
            'GC15' => [
                'response'             => '301',
                'message'              => 'Already active',
                'availableBalance'     => '14000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Activate Reversal result test data.
     *
     * @return array
     */
    private function getGiftCardActivateReversalResponseData()
    {
        $data = [
            'GC7R' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '0',
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
        $data = $this->getGiftCardActivateResponseData()
            + $this->getGiftCardActivateReversalResponseData();

        return $data[$id];
    }
}
