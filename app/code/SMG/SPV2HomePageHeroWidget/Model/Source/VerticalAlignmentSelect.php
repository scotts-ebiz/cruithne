<?php

namespace SMG\SPV2HomePageHeroWidget\Model\Source;

class VerticalAlignmentSelect implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'top', 'label' => __('top')],
            ['value' => 'middle', 'label' => __('middle')],
            ['value' => 'bottom', 'label' => __('bottom')]
        ];
    }
}
