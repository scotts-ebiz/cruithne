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
        $config = [
            'opt_in_status' => $opt_in_status,
			'opt_in_headline' => $opt_in_headline,
            'opt_in_disclaimer' => $opt_in_disclaimer,
			'storecode' => $this->getStoreCode(),
			'storename' => $this->getStoreName()	
        ];
        return $config;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }
	
	/**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        $storecode = $this->storeManager->getStore()->getCode();
        
        switch ($storecode) {
            case "scotts":
                return "scotts";
                break;
            case "miraclegro":
                return "miracle_gro";
                break;
            case "roundup":
                return "roundup";
                break;
            case "ortho":
                return "ortho";
                break;
            case "tomcat":
                return "tomcatbrand";
                break;  
            default:
                return $storecode;
        }
    }
    
    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        $storecode = $this->storeManager->getStore()->getCode();
        
        switch ($storecode) {
            case "scotts":
                return "Scotts";
                break;
            case "miraclegro":
                return "Miracle Gro";
                break;
            case "roundup":
                return "Roundup";
                break;
            case "ortho":
                return "Ortho";
                break;
            case "tomcat":
                return "Tomcat";
                break;  
            default:
                return $storecode;
        }
    }
}