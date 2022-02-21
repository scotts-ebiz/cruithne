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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardUnloadRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardUnloadReversalRenderer;

use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardUnloadResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardUnloadReversalResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardUnloadTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardUnloadRenderer
     */
    private $giftCardUnloadRenderer = null;

    /**
     * @var GiftCardUnloadRenderer
     */
    private $giftCardUnloadReversalRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardUnloadResponseParserFactory
     */
    private $giftCardUnloadResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardUnloadReversalResponseParserFactory
     */
    private $giftCardUnloadReversalResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardUnloadRenderer $giftCardUnloadRenderer
     * @param GiftCardUnloadReversalRenderer $giftCardUnloadReversalRenderer
     * @param GiftCardUnloadResponseParserFactory $giftCardUnloadResponseParserFactory
     * @param GiftCardUnloadReversalResponseParserFactory $giftCardUnloadReversalResponseParserFactory
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
        GiftCardUnloadRenderer $giftCardUnloadRenderer,
        GiftCardUnloadReversalRenderer $giftCardUnloadReversalRenderer,
        GiftCardUnloadResponseParserFactory $giftCardUnloadResponseParserFactory,
        GiftCardUnloadReversalResponseParserFactory $giftCardUnloadReversalResponseParserFactory,
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

        $this->giftCardUnloadRenderer = $giftCardUnloadRenderer;
        $this->giftCardUnloadReversalRenderer = $giftCardUnloadReversalRenderer;
        $this->giftCardUnloadResponseParserFactory = $giftCardUnloadResponseParserFactory;
        $this->giftCardUnloadReversalResponseParserFactory = $giftCardUnloadReversalResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeUnloadTests();
        $this->executeUnloadReversalTests();
    }

    /**
     * Run Unload tests
     *
     * @return void
     */
    private function executeUnloadTests()
    {
        /*
         * Test Gift Card Unload transactions.
         */
        $renderer = $this->getGiftCardUnloadRenderer();

        foreach ($this->getGiftCardUnloadRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardUnloadResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Unload, Dataset "' . $id . '"',
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
     * Run UnloadReversal tests
     *
     * @return void
     */
    private function executeUnloadReversalTests()
    {
        /*
         * Test Gift Card UnloadReversal transactions.
         */
        $renderer = $this->getGiftCardUnloadReversalRenderer();

        foreach ($this->getGiftCardUnloadReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardUnloadReversalResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', UnloadReversal, Dataset "' . $id . '"',
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
     * Get Unload Renderer instance.
     *
     * @return GiftCardUnloadRenderer
     */
    private function getGiftCardUnloadRenderer()
    {
        return $this->giftCardUnloadRenderer;
    }

    /**
     * Get UnloadReversal Renderer instance.
     *
     * @return GiftCardUnloadReversalRenderer
     */
    private function getGiftCardUnloadReversalRenderer()
    {
        return $this->giftCardUnloadReversalRenderer;
    }

    /**
     * Initialize Gift Card Unload parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardUnloadResponseParser
     */
    private function createGiftCardUnloadResponseParser($xml)
    {
        return $this->giftCardUnloadResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card UnloadReversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardUnloadReversalResponseParser
     */
    private function createGiftCardUnloadReversalResponseParser($xml)
    {
        return $this->giftCardUnloadReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card Unload data.
     *
     * @return array
     */
    private function getGiftCardUnloadRequestData()
    {
        $data = [
            'GC5' => [
                'orderId'           => 'GC5',
                'amount'            => '2500',
                'type'              => 'GC',
                'number'            => '6035716500000000004',
                'expDate'           => '0121',
                'cardValidationNum' => '123',
            ],
            'GC13' => [
                'orderId'           => 'GC13',
                'amount'            => '2500',
                'type'              => 'GC',
                'number'            => '6035712300000000003',
                'expDate'           => '0121',
                'cardValidationNum' => '123',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card UnloadReversal data.
     *
     * @return array
     */
    private function getGiftCardUnloadReversalRequestData()
    {
        $data = [
            'GC13A' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC13', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Unload result test data.
     *
     * @return array
     */
    private function getGiftCardUnloadResponseData()
    {
        $data = [
            'GC5' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '2500',
            ],
            'GC13' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '1500',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card UnloadReversal result test data.
     *
     * @return array
     */
    private function getGiftCardUnloadReversalResponseData()
    {
        $data = [
            'GC13A' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '4000',
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
        $data = $this->getGiftCardUnloadResponseData()
            + $this->getGiftCardUnloadReversalResponseData();

        return $data[$id];
    }
}
