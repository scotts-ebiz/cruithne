<?php

namespace SMG\ZipCodeList\Model\Source;

class TextAlignSelect implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1 - PAC', 'label' => __('1 - PAC')],
            ['value' => '2 - SOBO', 'label' => __('2 - SOBO')],
            ['value' => '3 - SOMIX', 'label' => __('3 - SOMIX')],
            ['value' => '4 - TB2', 'label' => __('4 - TB2')],
            ['value' => '5 - FLOBO', 'label' => __('5 - FLOBO')]
        ];
    }
}
