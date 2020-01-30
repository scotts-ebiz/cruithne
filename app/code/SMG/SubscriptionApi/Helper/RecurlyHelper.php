<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class RecurlyHelper
 * @package SMG\SubscriptionApi\Helper
 */
class RecurlyHelper extends AbstractHelper
{
    const RECURLY_CONFIG_ACTIVE = 'recurly/payment/active';
    const RECURLY_CONFIG_PRIVATE_API_KEY = 'recurly/payment/apikey';
    const RECURLY_CONFIG_PUBLIC_API_KEY = 'recurly/payment/publicapikey';
    const RECURLY_CONFIG_SUBDOMAIN = 'recurly/payment/subdomain';

    /**
     * Return state of Recurly payment
     *
     * @param null $store_id
     * @return bool
     */
    public function isActive($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::RECURLY_CONFIG_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * Return Recurly private API key
     *
     * @param null $store_id
     * @return string
     */
    public function getRecurlyPrivateApiKey($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::RECURLY_CONFIG_PRIVATE_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * Return Recurly public API key
     *
     * @param null $store_id
     * @return string
     */
    public function getRecurlyPublicApiKey($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::RECURLY_CONFIG_PUBLIC_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * Return Recurly subdomain
     *
     * @param null $store_id
     * @return string
     */
    public function getRecurlySubdomain($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::RECURLY_CONFIG_SUBDOMAIN,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * Return Recurly plan code or SKU from the season name
     *
     * @param string $season_name
     * @return string
     */
    public function getSeasonSlugByName($season_name)
    {
        switch ($season_name) {
            case 'Early Spring':
            case 'Early Spring Feeding':
                return 'early-spring';
            case 'Late Spring':
            case 'Late Spring Feeding':
            case 'Late Spring Seeding':
            case 'Late Spring Grub':
                return 'late-spring';
            case 'Early Summer':
            case 'Early Summer Feeding':
            case 'Early Summer Seeding':
                return 'early-summer';
            case 'Late Summer':
            case 'Late Summer Feeding':
                return 'late-summer';
            case 'Early Fall':
            case 'Early Fall Feeding':
            case 'Early Fall Seeding':
                return 'early-fall';
            case 'Late Fall':
            case 'Late Fall Feeding':
                return 'late-fall';
            default:
                return '';
        }
    }
}
