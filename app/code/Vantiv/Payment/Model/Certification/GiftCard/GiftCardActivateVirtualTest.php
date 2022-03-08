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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardActivateVirtualRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardActivateReversalRenderer;

use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateVirtualResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateReversalResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardActivateVirtualTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardActivateVirtualRenderer
     */
    private $giftCardActivateVirtualRenderer = null;

    /**
     * @var GiftCardActivateReversalRenderer
     */
    private $giftCardActivateReversalRenderer = null;

    /**
     * @var GiftCardActivateVirtualResponseParserFactory
     */
    private $giftCardActivateVirtualResponseParserFactory = null;

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
     * @param GiftCardActivateVirtualRenderer $giftCardActivateVirtualRenderer
     * @param GiftCardActivateReversalRenderer $giftCardActivateReversalRenderer
     * @param GiftCardActivateVirtualResponseParserFactory $giftCardActivateVirtualResponseParserFactory
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
        GiftCardActivateVirtualRenderer $giftCardActivateVirtualRenderer,
        GiftCardActivateReversalRenderer $giftCardActivateReversalRenderer,
        GiftCardActivateVirtualResponseParserFactory $giftCardActivateVirtualResponseParserFactory,
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

        $this->giftCardActivateVirtualRenderer = $giftCardActivateVirtualRenderer;
        $this->giftCardActivateReversalRenderer = $giftCardActivateReversalRenderer;
        $this->giftCardActivateVirtualResponseParserFactory = $giftCardActivateVirtualResponseParserFactory;
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
        $this->executeActivateVirtualTests();
        $this->executeActivateReversalTests();
    }

    /**
     * Run Activate Virtual tests
     *
     * @return void
     */
    private function executeActivateVirtualTests()
    {
        /*
         * Test Gift Card Activate Virtual transactions.
         */
        $renderer = $this->getGiftCardActivateVirtualRenderer();

        foreach ($this->getGiftCardActivateVirtualRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardActivateVirtualResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Activate Virtual, Dataset "' . $id . '"',
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
         * Test Virtual Gift Card Activate Reversal transactions.
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
                'name'         => $this->getName() . ', Activate Reversal Virtual Gift Card, Dataset "' . $id . '"',
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
     * Get Activate Virtual Renderer instance.
     *
     * @return GiftCardActivateVirtualRenderer
     */
    private function getGiftCardActivateVirtualRenderer()
    {
        return $this->giftCardActivateVirtualRenderer;
    }

    /**
     * Get Activate ReversalR enderer instance.
     *
     * @return GiftCardActivateReversalRenderer
     */
    private function getGiftCardActivateReversalRenderer()
    {
        return $this->giftCardActivateReversalRenderer;
    }

    /**
     * Initialize Gift Card Activate Virtual parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateVirtualResponseParser
     */
    private function createGiftCardActivateVirtualResponseParser($xml)
    {
        return $this->giftCardActivateVirtualResponseParserFactory->create(['xml' => $xml]);
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
     * Get Gift Card Activate Virtual data.
     *
     * @return array
     */
    private function getGiftCardActivateVirtualRequestData()
    {
        $data = [
            'GC1A' => [
                'orderId'             => 'GC1A',
                'amount'              => '8000',
                'accountNumberLength' => '16',
                'giftCardBin'         => '603571',
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
            'GC7B' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC1A', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Activate Virtual result test data.
     *
     * @return array
     */
    private function getGiftCardActivateVirtualResponseData()
    {
        $data = [
            'GC1A' => [
                'response'             => '000',
                'message'              => 'Approved',
                'availableBalance'     => '8000',
                'accountNumber'        => '603571xxxxxxxxxx',
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
            'GC7B' => [
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
        $data = $this->getGiftCardActivateVirtualResponseData()
            + $this->getGiftCardActivateReversalResponseData();

        return $data[$id];
    }
}
