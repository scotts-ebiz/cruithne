<?php

namespace SMG\Subscriptions\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper {
	/**
     * Active flag
     */
    const XML_PATH_ACTIVE = 'smg/general/active';

    /**
     * API Key
     */
    const XML_PATH_APIKEY = 'smg/general/apikey';

    /**
     * Subdomain
     */
    const XML_PATH_SUBDOMAIN = 'smg/general/subdomain';

    /**
     * Whether Recurly is ready to use
     *
     * @param null $store_id
     * @return bool
     */
    public function isEnabled($store_id = null)
    {
        $apikey = $this->scopeConfig->getValue(
            self::XML_PATH_APIKEY,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );

        $subdomain = $this->scopeConfig->getValue(
        	self::XML_PATH_SUBDOMAIN,
        	ScopeInterface::SCOPE_STORE,
        	$store_id
        );

        $active = $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );

        return $apikey && $subdomain && $active;
    }

    /**
     * Get Recurly API Key
     *
     * @param null $store_id
     * @return null | string
     */
    public function getApiKey($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_APIKEY,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * Get Recurly Subdomain
     *
     * @param null $store_id
     * @return null | string
     */
    public function getSubdomain($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SUBDOMAIN,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }
}