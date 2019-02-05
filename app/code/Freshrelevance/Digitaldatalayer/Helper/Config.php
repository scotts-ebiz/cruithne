<?php
namespace Freshrelevance\Digitaldatalayer\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    //DDLSettings
    const ENABLE_DDL='freshrelevance_ddl/ddl_settings/setting_1';
    const EXPOSE_PR_DATA_ON_ALL_PAGES='freshrelevance_ddl/ddl_settings/setting_2';
    const EXPOSE_PR_DATA_ON_THESE_PAGES='freshrelevance_ddl/ddl_settings/setting_3';
    const EXPOSE_PR_DATA_CUSTOM='freshrelevance_ddl/ddl_settings/setting_4';
    const EXPOSE_TRANSACTION_DATA='freshrelevance_ddl/ddl_settings/setting_5';
    const ENABLE_USER_GROUP_EXPOSURE='freshrelevance_ddl/ddl_settings/setting_6';
    const ENABLED_PRODUCT_ATTRIBUTES='freshrelevance_ddl/ddl_settings/setting_7';
    const ENABLE_STOCK_EXPOSURE='freshrelevance_ddl/ddl_settings/setting_8';
    const ENABLE_RATING_EXPOSURE='freshrelevance_ddl/ddl_settings/setting_9';
    const PRODUCT_LIST_EXPOSURE_TYPES='freshrelevance_ddl/ddl_settings/setting_10';
    const LINKED_PRODUCT_EXPOSURE='freshrelevance_ddl/ddl_settings/setting_11';
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getEnabledDdl()
    {
        return $this->getConfig(self::ENABLE_DDL);
    }
    public function getExposeProductDataOnAllPages()
    {
        return $this->getConfig(self::EXPOSE_PR_DATA_ON_ALL_PAGES);
    }
    public function getExposedDataPages()
    {
        $prData = $this->getConfig(self::EXPOSE_PR_DATA_ON_THESE_PAGES);
        $custom = $this->getConfig(self::EXPOSE_PR_DATA_CUSTOM);
        if($custom){
            if($prData){
                $prData = $prData . ',' . $custom;
            } else {
                $prData = $custom;
            }
        }
        return $prData;
    }
    public function getExposedTransactionPages()
    {
        return $this->getConfig(self::EXPOSE_TRANSACTION_DATA);
    }
    public function getUserGroupExposure()
    {
        return $this->getConfig(self::ENABLE_USER_GROUP_EXPOSURE);
    }
    public function getEnabledProductAttributes()
    {
        return explode(',', $this->getConfig(self::ENABLED_PRODUCT_ATTRIBUTES));
    }
    public function getEnabledStockExposure()
    {
        return $this->getConfig(self::ENABLE_STOCK_EXPOSURE);
    }
    public function getEnabledRatingExposure()
    {
        return $this->getConfig(self::ENABLE_RATING_EXPOSURE);
    }
    public function getEnabledProductListExposureTypes()
    {
        return $this->getConfig(self::PRODUCT_LIST_EXPOSURE_TYPES);
    }
    public function checkIsPageAvailableForDisposing($current_route)
    {
        if ($this->getEnabledDdl()==0) {
            return false;
        }
        if ($this->getExposeProductDataOnAllPages()==1) {
            return true;
        }
        return in_array($current_route, explode(',', $this->getExposedDataPages()));
    }
    public function linkedProductsAvailable()
    {
        return $this->getConfig(self::LINKED_PRODUCT_EXPOSURE);
    }
}
