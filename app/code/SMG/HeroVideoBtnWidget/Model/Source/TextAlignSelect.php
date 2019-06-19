<?php

namespace SMG\HeroVideoBtnWidget\Model\Source;

class TextAlignSelect implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [['value' => 'text-left', 'label' => __('Left aligned')], ['value' => 'text-right', 'label' => __('Right aligned')]];
    }
}
