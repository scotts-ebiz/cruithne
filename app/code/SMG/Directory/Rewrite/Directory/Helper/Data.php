<?php
namespace SMG\Directory\Rewrite\Directory\Helper;


class Data extends \Magento\Directory\Helper\Data
{
   protected $disallowed = [
    'Alaska',
    'American Samoa',
    'Armed Forces Africa',
    'Armed Forces Americas',
    'Armed Forces Canada',
    'Armed Forces Europe',
    'Armed Forces Middle East',
    'Armed Forces Pacific',
    'Federated States Of Micronesia',
    'Guam',
    'Hawaii',
    'Marshall Islands',
    'Northern Mariana Islands',
    'Palau',
    'Puerto Rico',
    'Virgin Islands'
    ];

    /**
     * Retrieve regions data json
     *
     * @return string
     */
    public function getRegionJson()
    {
        \Magento\Framework\Profiler::start('TEST: ' . __METHOD__, ['group' => 'TEST', 'method' => __METHOD__]);
        if (!$this->_regionJson) {
            $cacheKey = 'DIRECTORY_REGIONS_JSON_STORE' . $this->_storeManager->getStore()->getId();
            $json = $this->_configCacheType->load($cacheKey);
            if (empty($json)) {
                $regions = $this->getRegionData();
                if(isset($regions['US'])) {
                    $regions['US'] = array_filter($regions['US'], function ($region) {
                        if (isset($region['name']))
                            return !in_array($region['name'], $this->disallowed);
                        return true;
                    });
                }
                $json = $this->jsonHelper->jsonEncode($regions);
                if ($json === false) {
                    $json = 'false';
                }
                $this->_configCacheType->save($json, $cacheKey);
            }
            $this->_regionJson = $json;
        }

        \Magento\Framework\Profiler::stop('TEST: ' . __METHOD__);
        return $this->_regionJson;
    }
} 
