<?php

namespace SMG\SubscriptionApi\Cron;

use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\SeasonalHelper;

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
        SeasonalHelper $seasonalHelper
    ) {
        $this->_logger = $logger;
        $this->_seasonalHelper = $seasonalHelper;
    }

    public function execute()
    {
        $this->_logger->info("\n\nSUBSCRIPTION CRON\n\n");
        $this->_seasonalHelper->processSeasonalOrders();
    }
}
