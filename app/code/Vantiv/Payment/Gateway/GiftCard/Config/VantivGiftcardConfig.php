<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Config;

use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Vantiv payment configuration class.
 */
class VantivGiftcardConfig extends VantivCustomConfig
{
    /**
     * Gift cards account configuration namespace.
     *
     * @var string
     */
    const GIFTCARD_CONFIG_NS = 'vantiv/giftcard';

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        parent::__construct($scopeConfig, self::GIFTCARD_CONFIG_NS);
    }
}
