<?php
namespace SMG\Checkout\Block\Checkout;


class StateFilter
{
   protected $disallowed = [
    'American Samoa',
    'Armed Forces Africa',
    'Armed Forces Americas',
    'Armed Forces Canada',
    'Armed Forces Europe',
    'Armed Forces Middle East',
    'Armed Forces Pacific',
    'District of Columbia',
	'Federated States Of Micronesia',
	'Guam',
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

?>