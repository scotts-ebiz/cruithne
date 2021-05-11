<?php

namespace SMG\SPV2ContactWidget\Block\Widget;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class ContactForm extends Template implements BlockInterface
{
    protected $_template = "widget/contact-form.phtml";
    protected $_scopeConfig;

    const XML_PATH_ENABLED_FRONTEND = 'msp_securitysuite_recaptcha/frontend/enabled';
    const XML_PATH_PUBLIC_KEY       = 'msp_securitysuite_recaptcha/general/public_key';

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []

    )
    {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function getFrontendCaptchaEnabled()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_ENABLED_FRONTEND,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSecurityCaptchaKey()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_PUBLIC_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
