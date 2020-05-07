<?php
namespace SMG\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class SubscriptionOptinConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $store = $this->getStoreId();
        $opt_in_status = $this->scopeConfig->getValue('checkout/options/opt_in_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$opt_in_headline = $this->scopeConfig->getValue('checkout/options/opt_in_headline', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $opt_in_disclaimer = $this->scopeConfig->getValue('checkout/options/opt_in_disclaimer', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $opt_in_list_id = $this->scopeConfig->getValue('checkout/options/opt_in_list_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $opt_in_acquisition_source = $this->scopeConfig->getValue('checkout/options/opt_in_acquisition_source', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $config = [
            'opt_in_status' => $opt_in_status,
			'opt_in_headline' => $opt_in_headline,
            'opt_in_disclaimer' => $opt_in_disclaimer,
			'list_id' => $opt_in_list_id,
            'acquisition_source' => $opt_in_acquisition_source
        ];
        return $config;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }
}