<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DataObject;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Gateway\Common\Builder\AbstractLitleOnlineRequestBuilder;

/**
 * Abstract certification test model
 */
abstract class AbstractTest extends DataObject implements TestInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var VantivConfig
     */
    private $vantivConfig;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $environment = '';

    /**
     * Response parser factory.
     *
     * @var TestResponseParserFactory
     */
    protected $parserFactory = null;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Certification Test Result Factory
     *
     * @var ResultFactory
     */
    private $resultFactory = null;

    /**
     * Constructor
     *
     * @param Config $config
     * @param TestResponseParserFactory $parserFactory
     * @param StoreManagerInterface $storeManager
     * @param VantivConfig $vantivConfig
     * @param ResultFactory $resultFactory
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        ResultFactory $resultFactory,
        array $data = []
    ) {
        parent::__construct($data);

        $this->config = $config;
        $this->parserFactory = $parserFactory;
        $this->storeManager = $storeManager;
        $this->vantivConfig = $vantivConfig;

        $this->resultFactory = $resultFactory;
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
     * Get "active" flag
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->getConfig()->getValue($this->getPathPrefix() . self::ACTIVE);
    }

    /**
     * Get test name
     *
     * @return string
     */
    public function getName()
    {
        if ($this->name == '') {
            $this->name = $this->getConfig()->getValue($this->getPathPrefix(). self::NAME);
        }

        return $this->name;
    }

    /**
     * Get environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        if ($this->environment == '') {
            $this->environment = $this->getConfig()->getValue($this->getPathPrefix() . self::ENVIRONMENT);
        }

        return $this->environment;
    }

    /**
     * Get api request version
     *
     * @return string
     */
    public function getApiRequestVersion()
    {
        return AbstractLitleOnlineRequestBuilder::DEFAULT_VERSION;
    }

    /**
     * @return string
     */
    protected function getUniqueId()
    {
        return uniqid();
    }

    /**
     * Get Config object
     *
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Get VantivConfig object
     *
     * @return VantivConfig
     */
    protected function getVantivConfig()
    {
        return $this->vantivConfig;
    }

    /**
     * Get parser factory.
     *
     * @return TestResponseParserFactory
     */
    protected function getParserFactory()
    {
        return $this->parserFactory;
    }

    /**
     * Get authentication data
     *
     * @return string
     */
    protected function getAuthenticationData()
    {
        return '<authentication>
                    <user>' . $this->getVantivConfig()->getValue('username') . '</user>
                    <password>' . $this->getVantivConfig()->getValue('password') . '</password>
                </authentication>';
    }

    /**
     * Validate response data against expected test data.
     *
     * @param array $data
     * @param array $expected
     * @return boolean
     */
    protected function validate(array $data, array $expected)
    {
        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $data) || $data[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Persist test result data.
     *
     * @param array $data
     */
    protected function persistResult(array $data)
    {
        $result = $this->createResult();
        $result->load($data['test_id'], 'test_id');

        $result->setData('test_id', $data['test_id']);
        $result->setData('name', $data['name']);
        $result->setData('store_id', $data['store_id']);
        $result->setData('merchant_id', $data['merchant_id']);
        $result->setData('order_id', $data['order_id']);
        $result->setData('litle_txn_id', $data['litle_txn_id']);
        $result->setData('success_flag', $data['success_flag']);
        $result->setData('request', $data['request']);
        $result->setData('response', $data['response']);

        $result->save();
    }

    /**
     * Initialize result.
     *
     * @return \Vantiv\Payment\Model\Certification\Test\Result
     */
    private function createResult()
    {
        return $this->resultFactory->create();
    }

    /**
     * Get default test data.
     *
     * @return array
     */
    protected function getDefaultRequestData()
    {
        $data = [
            /* Authentication data. */
            'merchantId'   => $this->getVantivConfig()->getValue('merchant_id'),
            'user'         => $this->getVantivConfig()->getValue('username'),
            'password'     => $this->getVantivConfig()->getValue('password'),

            /* General purchase data. */
            'reportGroup'     => 'test',
            'customerId'      => 'test',
            'id'              => $this->getUniqueId(),
            'orderId'         => 'test',
            'orderSource'     => 'ecommerce',

            /* Billing address data. */
            'billToAddress'     => [
                'firstName'    => 'Test',
                'lastName'     => 'Test',
                'addressLine1' => '1 Test Street',
                'city'         => 'Test',
                'state'        => 'CA',
                'zip'          => '90064',
                'country'      => 'US',
                'email'        => 'test@example.com',
                'phone'        => '111-111-11111',
            ]
        ];

        return $data;
    }
}
