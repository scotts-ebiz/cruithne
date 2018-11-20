<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring\Config;

use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Vantiv subscription configuration class.
 */
class VantivSubscriptionConfig extends VantivCustomConfig
{
    /**
     * Recurring subscription configuration namespace.
     *
     * @var string
     */
    const SUBSCRIPTION_CONFIG_NS = 'vantiv/subscriptions';

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        parent::__construct($scopeConfig, self::SUBSCRIPTION_CONFIG_NS);
    }
}
