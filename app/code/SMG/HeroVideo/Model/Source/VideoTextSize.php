<?php

namespace SMG\HeroVideo\Model\Source;

class VideoTextSize implements \Magento\Framework\Option\ArrayInterface
{
  public function toOptionArray()
  {
    return [
      ['value' => 'font-size: 8px; ', 'label' => __('8px')],
      ['value' => 'font-size: 9px; ', 'label' => __('9px')],
      ['value' => 'font-size: 10px; ', 'label' => __('10px')],
      ['value' => 'font-size: 11px; ', 'label' => __('11px')],
      ['value' => 'font-size: 12px; ', 'label' => __('12px')],
      ['value' => 'font-size: 13px; ', 'label' => __('13px')],
      ['value' => 'font-size: 14px; ', 'label' => __('14px')],
      ['value' => 'font-size: 15px; ', 'label' => __('15px')],
      ['value' => 'font-size: 16px; ', 'label' => __('16px')],
      ['value' => 'font-size: 17px; ', 'label' => __('17px')],
      ['value' => 'font-size: 18px; ', 'label' => __('18px')],
      ['value' => 'font-size: 19px; ', 'label' => __('19px')],
      ['value' => 'font-size: 20px; ', 'label' => __('20px')]
    ];
  }
}
