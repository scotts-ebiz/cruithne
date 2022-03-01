<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParser;
use Vantiv\Payment\Gateway\Common\Builder\LitleOnlineRequestWrapper;
use Magento\Framework\Exception\LocalizedException;

/**
 * Abstract Subscriptions Test model
 */
abstract class AbstractSubscriptionsTest extends \Vantiv\Payment\Model\Certification\AbstractTest
{
    /**
     * @var string
     */
    const PATH_PREFIX = 'vantiv/subscriptions/';

    /**
     * @var string
     */
    const ID = 'subscriptions';

    /**
     * Certification Test Command
     *
     * @var Command
     */
    protected $command;

    /**
     * Certification Test Result Factory
     *
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper;

    /**
     * @var array
     */
    protected static $subscriptionIds = [];

    /**
     * Generated unique Plan codes
     *
     * @var array
     */
    public static $planCodes = [];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param \Vantiv\Payment\Gateway\Common\Builder\LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        Command $command,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        array $data = []
    ) {
        parent::__construct($config, $parserFactory, $storeManager, $vantivConfig, $resultFactory, $data);
        $this->command = $command;
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultRequestData()
    {
        $data = [
            /* Date data */
            'dateInFuture1' => '2030-01-01',
            'dateInFuture2' => '2031-01-01',
            'dateInFuture3' => '2032-01-01',
            'dateInPast'    => '2000-04-04',

            /* Credit Card data */
            'card1' => [
                'type' => 'VI',
                'number' => '4457010000000009',
                'expDate' => '0121',
            ],

            /* Order details */
            'amount' => '000',
        ];

        return array_merge_recursive(parent::getDefaultRequestData(), $data);
    }

    /**
     * Get path prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PATH_PREFIX;
    }

    /**
     * Get feature id
     *
     * @return string
     */
    public function getId()
    {
        return self::ID;
    }

    /**
     * @param string $dataNodeXml
     * @return string
     */
    public function createAuthenticationWrapper($dataNodeXml)
    {
        return $this->litleOnlineRequestWrapper->wrap(
            $dataNodeXml,
            $this->getVantivConfig()->getValue('merchant_id'),
            $this->getVantivConfig()->getValue('username'),
            $this->getVantivConfig()->getValue('password'),
            $this->getApiRequestVersion()
        );
    }

    /**
     * @param boolean $status
     * @param string $orderId
     * @param string $litleTxnId
     * @param string $requestXml
     * @param string $responseXml
     * @return void
     */
    protected function saveResultData($status, $orderId, $litleTxnId, $requestXml, $responseXml)
    {
        $this->persistResult([
            'test_id' => self::ID . '#' . $orderId,
            'name' => $this->getName() . ', Subscription, Dataset "' . $orderId . '"',
            'store_id' => $this->storeManager->getStore()->getId(),
            'merchant_id' => $this->getVantivConfig()->getValue('merchant_id'),
            'order_id' => $orderId,
            'litle_txn_id' => $litleTxnId,
            'success_flag' => (int) $status,
            'request' => $requestXml,
            'response' => $responseXml,
        ]);
    }

    /**
     * @param string $orderId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getSubscriptionIdByOrderId($orderId)
    {
        if (strpos($orderId, 'R') !== 0) {
            return $orderId;
        }
        if (isset(self::$subscriptionIds[$orderId])) {
            return self::$subscriptionIds[$orderId];
        }
        throw new LocalizedException(__("Test result for Order\"{$orderId}\" not found"));
    }

    /**
     * @param string $orderId
     * @param int $subscriptionId
     * @return $this
     */
    protected function setSubscriptionId($orderId, $subscriptionId)
    {
        self::$subscriptionIds[$orderId] = $subscriptionId;

        return $this;
    }

    /**
     * @param TestResponseParser $testResponseParser
     * @param array $data
     * @return bool
     */
    protected function validateResponse(TestResponseParser $testResponseParser, array $data)
    {
        foreach ($data as $field => $value) {
            if ($testResponseParser->getValue($field) != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $xml
     * @return TestResponseParser
     */
    protected function createTestResponseParser($xml)
    {
        return $this->getParserFactory()->create(['xml' => $xml]);
    }

    /**
     * @param string $requestXml
     * @param string $responseRoot
     * @return \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParser
     */
    protected function getTestResponse($requestXml, $responseRoot)
    {
        $response = $this->command->call($requestXml);

        $parser = $this->createTestResponseParser($response);
        $parser->setPathPrefix($responseRoot);

        return $parser;
    }

    /**
     * @param string $reference
     * @return string
     */
    protected function getPlanCode($reference)
    {
        $planCode = $this->getData('planCodes/' . $reference);
        return ($planCode === null) ? $reference : $planCode;
    }
}
