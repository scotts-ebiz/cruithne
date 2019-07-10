<?php

namespace SMG\HeroVideoBtnWidget\Model\Source;

class TextSize implements \Magento\Framework\Option\ArrayInterface
{
  public function toOptionArray()
  {
    return [
      ['value' => 'font-size: 0.8rem; ', 'label' => __('0.8')],
      ['value' => 'font-size: 0.9rem; ', 'label' => __('0.9')],
      ['value' => 'font-size: 1.0rem; ', 'label' => __('1.0')],
      ['value' => 'font-size: 1.1rem; ', 'label' => __('1.1')],
      ['value' => 'font-size: 1.2rem; ', 'label' => __('1.2')],
      ['value' => 'font-size: 1.3rem; ', 'label' => __('1.3')],
      ['value' => 'font-size: 1.4rem; ', 'label' => __('1.4')],
      ['value' => 'font-size: 1.5rem; ', 'label' => __('1.5')],
      ['value' => 'font-size: 1.6rem; ', 'label' => __('1.6')],
      ['value' => 'font-size: 1.7rem; ', 'label' => __('1.7')],
      ['value' => 'font-size: 1.8rem; ', 'label' => __('1.8')],
      ['value' => 'font-size: 1.9rem; ', 'label' => __('1.9')],
      ['value' => 'font-size: 2.0rem; ', 'label' => __('2.0')]
    ];
  }
}
