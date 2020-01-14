<?php

namespace SMG\SubscriptionApi\Cron;

use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\SeasonalHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SubscriptionCron
 * @package SMG\SubscriptionApi\Cron
 */
class SubscriptionCron
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;
    /**
     * @var SeasonalHelper
     */
    protected $_seasonalHelper;

    /**
     * SubscriptionCron constructor.
     * @param LoggerInterface $logger
     * @param SeasonalHelper $seasonalHelper
     */
    public function __construct(
        LoggerInterface $logger,
        SeasonalHelper $seasonalHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->_logger = $logger;
        $this->_seasonalHelper = $seasonalHelper;
        $this->_storeManager = $storeManager;
    }

    public function execute()
    {
        $this->_logger->info("Running Seasonal Subscription Cron");
        $store = 'main_website_store';
        $this->_storeManager->setCurrentStore($store);
        $this->_seasonalHelper->processSeasonalOrders();
    }
}
