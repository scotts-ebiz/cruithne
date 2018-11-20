<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Fraud;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Vantiv\Payment\Model\Session;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as Config;

class ThreatMetrix extends Template
{
    /**
     * Common Vantiv configuration instance.
     *
     * @var Config
     */
    private $config = null;

    /**
     * Vantiv session instance.
     *
     * @var Session
     */
    private $session = null;

    /**
     * ThreatMetrix Constructor.
     *
     * @param Config $config
     * @param Session $session
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Config $config,
        Session $session,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Private session getter.
     *
     * @return Session
     */
    private function getSession()
    {
        return $this->session;
    }

    /**
     * Get online metrix URL params.
     *
     * @return string[]
     */
    private function getUrlParams()
    {
        $params = [
            'org_id'     => $this->getOrgId(),
            'session_id' => $this->getSessionId(),
            'pageid'     => $this->getPageId(),
        ];

        return $params;
    }

    /**
     * Get online metrix params URL-encoded string.
     *
     * @return string
     */
    private function getUrlParamsString()
    {
        return http_build_query($this->getUrlParams(), '', '&');
    }

    /**
     * Get URL for JS reference.
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return 'https://h.online-metrix.net/fp/tags.js?' . $this->getUrlParamsString();
    }

    /**
     * Get URL for iFrame reference.
     *
     * @return string
     */
    public function getNoscriptUrl()
    {
        return 'https://h.online-metrix.net/tags?' . $this->getUrlParamsString();
    }

    /**
     * Current method is reserved.
     *
     * The pageid tag is not used at this time.
     * The value for 'PAGE-ID' will default to 1.
     *
     * @return string
     */
    public function getPageId()
    {
        return '1';
    }

    /**
     * The value for 'ORG-ID' is a Vantiv supplied value.
     *
     * @return string
     */
    public function getOrgId()
    {
        return $this->config->getValue('threatmetrix_orgid');
    }

    /**
     * Uniquely generated handle that includes the Vantiv supplied prefix.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->getSession()->getUniqueSessionId();
    }
}
