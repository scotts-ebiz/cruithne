<?php

namespace SMG\SPV2HomePageHeroWidget\Model\Source;

class BackgroundPositionSelect implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'sp-bg-center', 'label' => __('center')],
            ['value' => 'sp-bg-bottom', 'label' => __('bottom')],
            ['value' => 'sp-bg-left', 'label' => __('left')],
            ['value' => 'sp-bg-left-bottom', 'label' => __('left bottom')],
            ['value' => 'sp-bg-left-top', 'label' => __('left top')],
            ['value' => 'sp-bg-right', 'label' => __('right')],
            ['value' => 'sp-bg-right-bottom', 'label' => __('right bottom')],
            ['value' => 'sp-bg-right-top', 'label' => __('right top')],
            ['value' => 'sp-bg-top', 'label' => __('top')]
        ];
    }
}
