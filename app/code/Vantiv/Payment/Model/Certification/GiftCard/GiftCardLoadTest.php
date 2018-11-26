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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardLoadRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardLoadReversalRenderer;

use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardLoadResponseParserFactory;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardLoadReversalResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardLoadTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardLoadRenderer
     */
    private $giftCardLoadRenderer = null;

    /**
     * @var GiftCardLoadRenderer
     */
    private $giftCardLoadReversalRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardLoadResponseParserFactory
     */
    private $giftCardLoadResponseParserFactory = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardLoadReversalResponseParserFactory
     */
    private $giftCardLoadReversalResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardLoadRenderer $giftCardLoadRenderer
     * @param GiftCardLoadReversalRenderer $giftCardLoadReversalRenderer
     * @param GiftCardLoadResponseParserFactory $giftCardLoadResponseParserFactory
     * @param GiftCardLoadReversalResponseParserFactory $giftCardLoadReversalResponseParserFactory
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
        GiftCardLoadRenderer $giftCardLoadRenderer,
        GiftCardLoadReversalRenderer $giftCardLoadReversalRenderer,
        GiftCardLoadResponseParserFactory $giftCardLoadResponseParserFactory,
        GiftCardLoadReversalResponseParserFactory $giftCardLoadReversalResponseParserFactory,
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

        $this->giftCardLoadRenderer = $giftCardLoadRenderer;
        $this->giftCardLoadReversalRenderer = $giftCardLoadReversalRenderer;
        $this->giftCardLoadResponseParserFactory = $giftCardLoadResponseParserFactory;
        $this->giftCardLoadReversalResponseParserFactory = $giftCardLoadReversalResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeLoadTests();
        $this->executeLoadReversalTests();
    }

    /**
     * Run Load tests
     *
     * @return void
     */
    private function executeLoadTests()
    {
        /*
         * Test Gift Card Load transactions.
         */
        $renderer = $this->getGiftCardLoadRenderer();

        foreach ($this->getGiftCardLoadRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardLoadResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Load, Dataset "' . $id . '"',
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
     * Run LoadReversal tests
     *
     * @return void
     */
    private function executeLoadReversalTests()
    {
        /*
         * Test Gift Card LoadReversal transactions.
         */
        $renderer = $this->getGiftCardLoadReversalRenderer();

        foreach ($this->getGiftCardLoadReversalRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardLoadReversalResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', LoadReversal, Dataset "' . $id . '"',
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
     * Get Load Renderer instance.
     *
     * @return GiftCardLoadRenderer
     */
    private function getGiftCardLoadRenderer()
    {
        return $this->giftCardLoadRenderer;
    }

    /**
     * Get LoadReversal Renderer instance.
     *
     * @return GiftCardLoadReversalRenderer
     */
    private function getGiftCardLoadReversalRenderer()
    {
        return $this->giftCardLoadReversalRenderer;
    }

    /**
     * Initialize Gift Card Load parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardLoadResponseParser
     */
    private function createGiftCardLoadResponseParser($xml)
    {
        return $this->giftCardLoadResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Initialize Gift Card LoadReversal parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardLoadReversalResponseParser
     */
    private function createGiftCardLoadReversalResponseParser($xml)
    {
        return $this->giftCardLoadReversalResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card Load data.
     *
     * @return array
     */
    private function getGiftCardLoadRequestData()
    {
        $data = [
            'GC4' => [
                'orderId'           => 'GC4',
                'amount'            => '5000',
                'type'              => 'GC',
                'number'            => '6035716500000000004',
                'expDate'           => '0121',
                'cardValidationNum' => '123',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card LoadReversal data.
     *
     * @return array
     */
    private function getGiftCardLoadReversalRequestData()
    {
        $data = [
            'GC12' => [
                'litleTxnId' => $this->getCachedResponseValueById('GC4', 'litleTxnId'),
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card Load result test data.
     *
     * @return array
     */
    private function getGiftCardLoadResponseData()
    {
        $data = [
            'GC4' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '5000',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card LoadReversal result test data.
     *
     * @return array
     */
    private function getGiftCardLoadReversalResponseData()
    {
        $data = [
            'GC12' => [
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
        $data = $this->getGiftCardLoadResponseData()
            + $this->getGiftCardLoadReversalResponseData();

        return $data[$id];
    }
}
