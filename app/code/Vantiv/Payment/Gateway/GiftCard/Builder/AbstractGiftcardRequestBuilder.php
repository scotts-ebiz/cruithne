<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Builder;

use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\Common\Builder\AbstractCustomRequestBuilder;

/**
 * Vantiv XML request builder.
 *
 * @api
 */
abstract class AbstractGiftcardRequestBuilder extends AbstractCustomRequestBuilder
{
    /**
     * Account number length (Vantiv recommended value)
     *
     * @var string
     */
    const ACCOUNT_NUMBER_LENGTH = 16;

    /**
     * Gift Card Type
     *
     * @var string
     */
    const GIFT_CARD_TYPE = 'GC';

    /**
     * Configuration instance.
     *
     * @var VantivGiftcardConfig
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param VantivGiftcardConfig $config
     */
    public function __construct(
        SubjectReader $reader,
        VantivGiftcardConfig $config
    ) {
        parent::__construct($reader);

        $this->config = $config;
    }

    /**
     * Get configuration instance.
     *
     * @return VantivGiftcardConfig
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Read API merchant ID.
     *
     * @param array $subject
     * @return string
     */
    protected function readMerchant(array $subject)
    {
        return $this->getConfig()->getValue('merchant_id');
    }

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    protected function readUsername(array $subject)
    {
        return $this->getConfig()->getValue('username');
    }

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    protected function readPassword(array $subject)
    {
        return $this->getConfig()->getValue('password');
    }
}
