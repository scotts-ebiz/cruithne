<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model;

use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as Config;
use Magento\Framework\Math\Random as Generator;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * ThreatMetrix session model.
 */
class Session
{
    /**
     * Configuration model instance.
     *
     * @var Config
     */
    private $config = null;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $generator = null;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession = null;

    /**
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $config
     * @param \Magento\Framework\Math\Random $generator
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(Config $config, Generator $generator, CheckoutSession $checkoutSession)
    {
        $this->config = $config;
        $this->generator = $generator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Return uniques public session ID.
     *
     * @return string
     */
    public function getUniqueSessionId()
    {
        if ($this->checkoutSession->getVantivSessionId()) {
            return $this->checkoutSession->getVantivSessionId();
        }

        $sessionId = $this->generateUniqueId();
        $this->checkoutSession->setVantivSessionId($sessionId);

        return $sessionId;
    }

    /**
     * Generate unique session identifier.
     *
     * @return string
     */
    public function generateUniqueId()
    {
        return $this->generator->getUniqueHash($this->getSessionPrefix());
    }

    /**
     * Read session prefix from configuration.
     *
     * @return string
     */
    public function getSessionPrefix()
    {
        return $this->config->getValue('threatmetrix_sessionprefix');
    }
}
