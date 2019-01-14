<?php

namespace SMG\HeroCallout\Model\Source;

class HeroCalloutBannerHeadlineSize implements \Magento\Framework\Option\ArrayInterface
{
  public function toOptionArray()
  {
    return [
        ['value' => 'font-size: 2.0rem; ', 'label' => __('2.0')],
        ['value' => 'font-size: 2.1rem; ', 'label' => __('2.1')],
        ['value' => 'font-size: 2.2rem; ', 'label' => __('2.2')],
        ['value' => 'font-size: 2.3rem; ', 'label' => __('2.3')],
        ['value' => 'font-size: 2.4rem; ', 'label' => __('2.4')],
        ['value' => 'font-size: 2.5rem; ', 'label' => __('2.5')],
        ['value' => 'font-size: 2.6rem; ', 'label' => __('2.6')],
        ['value' => 'font-size: 2.7rem; ', 'label' => __('2.7')],
        ['value' => 'font-size: 2.8rem; ', 'label' => __('2.8')],
        ['value' => 'font-size: 2.9rem; ', 'label' => __('2.9')],
        ['value' => 'font-size: 3.0rem; ', 'label' => __('3.0')]
    ];
  }
}
