<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\ShipTracking\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Track
 * @package SMG\ShipTracking\Model
 */
class ConfigProvider
{
    /**
     * @var string
     */
    const SHIP_TRACKING_CONFIG = 'ship_tracking/trackers/urls';

    /**
     * @var string[]
     */
    private $parsedTrackingUrls;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieves the tracking url for a specific service
     *
     * @param int $storeId
     * @param string $service
     * @return string
     */
    public function getTrackingUrlForService($storeId, $service)
    {
        $trackingUrls = $this->getParsedTrackingUrls($storeId);

        return $trackingUrls[strtoupper($service)] ?? $trackingUrls['default'];
    }

    /**
     * Grabs the tracking URLs from admin config and parses them
     *
     * @param int $storeId
     * @return array
     */
    private function getParsedTrackingUrls($storeId)
    {
        if (is_null($this->parsedTrackingUrls)) {
            $parsedTrackingUrls = [
                'default' => 'https://google.com/search?q={{code}}'
            ];

            $unparsedTrackingUrls = $this->scopeConfig->getValue(
                self::SHIP_TRACKING_CONFIG,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            foreach (preg_split("/\r\n|\n|\r/", $unparsedTrackingUrls) as $entry) {
                $entryParts = explode("|", $entry);
                $parsedTrackingUrls[strtoupper($entryParts[0])] = $entryParts[1];
            }

            $this->parsedTrackingUrls = $parsedTrackingUrls;
        }

        return $this->parsedTrackingUrls;
    }
}
