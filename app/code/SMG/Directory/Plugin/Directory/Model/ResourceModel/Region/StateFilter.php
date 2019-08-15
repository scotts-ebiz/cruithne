<?php
namespace SMG\Directory\Plugin\Directory\Model\ResourceModel\Region;


class StateFilter
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

    public function afterToOptionArray(\Magento\Directory\Model\ResourceModel\Region\Collection $subject, $options)
    {
        $result = array_filter($options, function ($option){
            if(isset($option['label']))
                return !in_array($option['label'], $this->disallowed);
            return true;
        });

        return $result;
    }
}