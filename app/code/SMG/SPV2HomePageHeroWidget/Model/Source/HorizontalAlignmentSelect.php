<?php

namespace SMG\SPV2HomePageHeroWidget\Model\Source;

class HorizontalAlignmentSelect implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'left', 'label' => __('left')],
            ['value' => 'center', 'label' => __('center')],
            ['value' => 'right', 'label' => __('right')]
        ];
    }
}
