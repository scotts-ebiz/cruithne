<?php

namespace SMG\ParameterTypes\Model\Source;

class YesNoSelect implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('No')],
            ['value' => '1', 'label' => __('Yes')]
        ];
    }
}
