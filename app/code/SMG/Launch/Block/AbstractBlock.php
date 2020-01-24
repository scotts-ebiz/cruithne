<?php
namespace SMG\Launch\Block;

use Magento\Framework\View\Element\Template;

class AbstractBlock	extends \Magento\Framework\View\Element\Template {

    protected $_scopeConfig;

    protected $_storeManager;

    public function __construct(
        Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        parent::__construct($context, $data);
	}

    public function getScript() {
        return $this->_scopeConfig->getValue("smg_launch/adobe/url",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId());
    }

    public function isSmgEnabled() {
        return $this->_scopeConfig->getValue('smg_launch/adobe/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId());
    }
}