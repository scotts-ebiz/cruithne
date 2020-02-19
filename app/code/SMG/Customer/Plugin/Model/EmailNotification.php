<?php
namespace SMG\Customer\Plugin\Model;

class EmailNotification
{
	const XML_CONFIG_SEND_WELCOME_EMAIL = 'customer/create_account/send_welcome_email';
	private $_logger;
	private $_scopeConfig;
	
	public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
		$this->logger = $logger;
    }

	public function aroundNewAccount(\Magento\Customer\Model\EmailNotification $subject, \Closure $proceed)
	{
		 if(!$this->getWelcomeEmailConfig()){
			 $this->logger->info('Magento signup email Disabled ');
			return $subject; 
		 }else
		 {
			 $this->logger->info('Magento signup email sent');
		 }
	}
	
	public function getWelcomeEmailConfig() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
     return $this->scopeConfig->getValue(self::XML_CONFIG_SEND_WELCOME_EMAIL, $storeScope);
    }
}
