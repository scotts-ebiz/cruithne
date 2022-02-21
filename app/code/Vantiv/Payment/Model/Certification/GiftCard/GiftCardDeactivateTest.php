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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardDeactivateRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardDeactivateReversalRenderer;

use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateReversalResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardDeactivateTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardDeactivateRenderer
     */
    private $giftCardDeactivateRenderer = null;

    /**
     * @var GiftCardDeactivateReversalRenderer
     */
    private $giftCardDeactivateReversalRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateResponseParser
     */
    private $giftCardDeactivateResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateReversalResponseParser
     */
    private $giftCardDeactivateReversalResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardDeactivateRenderer $giftCardDeactivateRenderer
     * @param GiftCardDeactivateReversalRenderer $giftCardDeactivateReversalRenderer
     * @param GiftCardDeactivateResponseParserFactory $giftCardDeactivateResponseParserFactory
     * @param GiftCardDeactivateReversalResponseParserFactory $giftCardDeactivateReversalResponseParserFactory
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
        GiftCardDeactivateRenderer $giftCardDeactivateRenderer,
        GiftCardDeactivateReversalRenderer $giftCardDeactivateReversalRenderer,
        GiftCardDeactivateResponseParserFactory $giftCardDeactivateResponseParserFactory,
        GiftCardDeactivateReversalResponseParserFactory $giftCardDeactivateReversalResponseParserFactory,
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

        $this->giftCardDeactivateRenderer = $giftCardDeactivateRenderer;
        $this->giftCardDeactivateReversalRenderer = $giftCardDeactivateReversalRenderer;
        $this->giftCardDeactivateResponseParserFactory = $giftCardDeactivateResponseParserFactory;
        $this->giftCardDeactivateReversalResponseParserFactory = $giftCardDeactivateReversalResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeDeactivateTests();
        $this->executeDeactivateReversalTests();
    }

    /**
     * Run Deactivate tests
     *
     * @return void
     */
    private function executeDeactivateTests()
    {
        /*
         * Test Gift Card Deactivate transactions.
         */
        $renderer = $this->getGiftCardDeactivateRenderer();

        foreach ($this->getGiftCardDeactivateRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardDeactivateResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Deactivate, Dataset "' . $id . '"',
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
     * Run Deactivate Reversal tests
     *
     * @return void
     */
    private function executeDeactivateReversalTests()
    {
        /*
         * Test Gift Card Deactivate Reversal transactions.
         */
        $renderer = $this->getGiftCardDeactivateReversalRenderer();

        foreach ($this->getGiftCardDeactivateReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardDeactivateReversalResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Deactivate Reversal Gift Card, Dataset "' . $id . '"',
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
     * Get Deactivate Renderer instance.
     *
     * @return GiftCardDeactivateRenderer
     */
    private function getGiftCardDeactivateRenderer()
    {
        return $this->giftCardDeactivateRenderer;
    }

    /**
     * Get Deactivate Reversal Renderer instance.
     *
     * @return GiftCardDeactivateReversalRenderer
     */
    private function getGiftCardDeactivateReversalRenderer()
    {
        return $this->giftCardDeactivateReversalRenderer;
    }

    /**
     * Initialize Gift Card Deactivate parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateResponseParser
     */
    private function createGiftCardDeactivateResponseParser($xml)
    {
        return $this->giftCardDeactivateResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card Deactivate Reversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateReversalResponseParser
     */
    private function createGiftCardDeactivateReversalResponseParser($xml)
    {
        return $this->giftCardDeactivateReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card Deactivate data.
     *
     * @return array
     */
    private function getGiftCardDeactivateRequestData()
    {
        $data = [
            'CG3' => [
                'orderId'           => 'CG3',
                'type'              => 'GC',
                'number'            => '6035716400000000007',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Deactivate Reversal data.
     *
     * @return array
     */
    private function getGiftCardDeactivateReversalRequestData()
    {
        $data = [
            'GC11' => [
                'litleTxnId' => $this->getCachedResponseValueById('CG3', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Deactivate result test data.
     *
     * @return array
     */
    private function getGiftCardDeactivateResponseData()
    {
        $data = [
            'CG3' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '0',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Deactivate Reversal result test data.
     *
     * @return array
     */
    private function getGiftCardDeactivateReversalResponseData()
    {
        $data = [
            'GC11' => [
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
        $data = $this->getGiftCardDeactivateResponseData()
            + $this->getGiftCardDeactivateReversalResponseData();

        return $data[$id];
    }
}
