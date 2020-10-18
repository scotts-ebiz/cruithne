<?php

namespace SMG\BackendService\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\CcConfig;

class Data extends AbstractHelper
{

    const XML_API_ORDER_REQUEST_URI = 'smg_backendservice/api/order';
    const XML_API_CUSTOMER_REQUEST_URI = 'smg_backendservice/api/customer';
    const WEB_SOURCE = 'WEB';

    public $ccConfig;
    /**
     * Config constructor
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CcConfig $ccConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->ccConfig = $ccConfig;
    }

    /**
     * @return string
     */
    public function getOrderApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::XML_API_ORDER_REQUEST_URI,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getCustomerApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::XML_API_CUSTOMER_REQUEST_URI,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function generateUuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0C2f) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0x2Aff), mt_rand(0, 0xffD3), mt_rand(0, 0xff4B)
        );
    }
    
    /**
     * @return string
     */
    public function getCardFullName($CardCode)
    {
        $return = "";
        if($CardCode){
            
            $cardTypes = $this->ccConfig->getCcAvailableTypes();

            if(array_key_exists($CardCode, $cardTypes))
            {
              $return = $cardTypes[$CardCode];
            }
            else if($CardCode == 'AX')
            {
                $return = 'Amex';
            }
        }
        return $return; // Visa / American Express ...
    }

    /**
     * @return string
     */
    function getWebSource()
    {
        return self::WEB_SOURCE;
    }
}
