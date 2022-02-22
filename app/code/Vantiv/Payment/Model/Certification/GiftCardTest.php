<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

/**
 * Certification test model
 */
class GiftCardTest extends AbstractTest
{
    /**
     * @var string
     */
    const PATH_PREFIX = 'vantiv/giftcard/';

    /**
     * @var string
     */
    const ID = 'vantiv_gift_card';

    /**
     * Certification Test Command
     *
     * @var Command
     */
    protected $command;

    /**
     * @var EntityFactoryInterface
     */
    private $entityFactory;

    /**
     * @var array
     */
    private $cachedResponseData = [];

    /**
     * @var array
     */
    private $testsToRun = [
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardActivateTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardActivateVirtualTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardDeactivateTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardAuthorizationTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardSaleTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardLoadTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardUnloadTest',
        'Vantiv\Payment\Model\Certification\GiftCard\GiftCardBalanceInquiryTest',
    ];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Certification\TestCommand $command
     * @param \Vantiv\Payment\Model\Certification\Test\ResultFactory $resultFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $vantivConfig
     * @param EntityFactoryInterface $entityFactory
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
        array $data = []
    ) {
        parent::__construct(
            $config,
            $parserFactory,
            $storeManager,
            $vantivConfig,
            $resultFactory,
            $data
        );

        $this->command = $command;
        $this->entityFactory = $entityFactory;
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
     * Run Gift Card Tests
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        foreach ($this->testsToRun as $testModelName) {
            $testInstance = $this->entityFactory->create($testModelName);
            $testInstance->execute($subject);
        }
    }

    /**
     * Save cached response data.
     *
     * @param string $id
     * @param array $data
     * @return void
     */
    protected function setCachedResponseData($id, array $data)
    {
        $this->cachedResponseData[$id] = $data;
    }

    /**
     * Get cached response data.
     *
     * @param string $id
     * @return array
     */
    protected function getCachedResponseDataById($id)
    {
        return array_key_exists($id, $this->cachedResponseData)
            ? $this->cachedResponseData[$id]
            : [];
    }

    /**
     * Get cached response value.
     *
     * @param string $id
     * @param string $key
     * @return null|string
     */
    protected function getCachedResponseValueById($id, $key)
    {
        $data = $this->getCachedResponseDataById($id);
        $value = array_key_exists($key, $data) ? $data[$key] : null;

        return $value;
    }
}
