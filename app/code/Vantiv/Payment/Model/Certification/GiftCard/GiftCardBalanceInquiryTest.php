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

use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardBalanceInquiryRenderer;

use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardBalanceInquiryResponseParserFactory;

/**
 * Certification test model
 */
class GiftCardBalanceInquiryTest extends \Vantiv\Payment\Model\Certification\GiftCardTest
{
    /**
     * @var GiftCardBalanceInquiryRenderer
     */
    private $giftCardBalanceInquiryRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardBalanceInquiryResponseParserFactory
     */
    private $giftCardBalanceInquiryResponseParserFactory = null;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
     * @param GiftCardBalanceInquiryRenderer $giftCardBalanceInquiryRenderer
     * @param GiftCardBalanceInquiryResponseParserFactory $giftCardBalanceInquiryResponseParserFactory
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
        GiftCardBalanceInquiryRenderer $giftCardBalanceInquiryRenderer,
        GiftCardBalanceInquiryResponseParserFactory $giftCardBalanceInquiryResponseParserFactory,
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

        $this->giftCardBalanceInquiryRenderer = $giftCardBalanceInquiryRenderer;
        $this->giftCardBalanceInquiryResponseParserFactory = $giftCardBalanceInquiryResponseParserFactory;
    }

    /**
     * Run Gift Card Test
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $this->executeBalanceInquiryTests();
    }

    /**
     * Run BalanceInquiry tests
     *
     * @return void
     */
    private function executeBalanceInquiryTests()
    {
        /*
         * Test Gift Card BalanceInquiry transactions.
         */
        $renderer = $this->getGiftCardBalanceInquiryRenderer();

        foreach ($this->getGiftCardBalanceInquiryRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createGiftCardBalanceInquiryResponseParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $this->setCachedResponseData($id, $responseData);
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', BalanceInquiry, Dataset "' . $id . '"',
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
     * Get BalanceInquiry Renderer instance.
     *
     * @return GiftCardBalanceInquiryRenderer
     */
    private function getGiftCardBalanceInquiryRenderer()
    {
        return $this->giftCardBalanceInquiryRenderer;
    }

    /**
     * Initialize Gift Card BalanceInquiry parser instance.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardBalanceInquiryResponseParser
     */
    private function createGiftCardBalanceInquiryResponseParser($xml)
    {
        return $this->giftCardBalanceInquiryResponseParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get Gift Card BalanceInquiry data.
     *
     * @return array
     */
    private function getGiftCardBalanceInquiryRequestData()
    {
        $data = [
            'GC6' => [
                'orderId'           => 'GC6',
                'type'              => 'GC',
                'number'            => '6035716390000000000',
                'expDate'           => '0121',
                'cardValidationNum' => '123',
            ],
            'GC20' => [
                'orderId'           => 'GC20',
                'type'              => 'GC',
                'number'            => '6035716500000000004',
                'expDate'           => '0121',
                'cardValidationNum' => '476',
            ],
        ];

        return $data;
    }

    /**
     * Get Gift Card BalanceInquiry result test data.
     *
     * @return array
     */
    private function getGiftCardBalanceInquiryResponseData()
    {
        $data = [
            'GC6' => [
                'response'             => '000',
                'message'              => 'Approved',
                'cardValidationResult' => 'M',
                'availableBalance'     => '2500',
            ],
            'GC20' => [
                'response'             => '352',
                'message'              => 'Invalid CVV2',
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
        $data = $this->getGiftCardBalanceInquiryResponseData();

        return $data[$id];
    }
}
