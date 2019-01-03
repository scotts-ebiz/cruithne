<?php

namespace SMG\ProductGrid\Model\Source;

class productHeadlineTextSize implements \Magento\Framework\Option\ArrayInterface
{
  public function toOptionArray()
  {
    return [
        ['value' => 'font-size: 1.6rem; ', 'label' => __('1.6')],
        ['value' => 'font-size: 1.7rem; ', 'label' => __('1.7')],
        ['value' => 'font-size: 1.8rem; ', 'label' => __('1.8')],
        ['value' => 'font-size: 1.9rem; ', 'label' => __('1.9')],
        ['value' => 'font-size: 2.0rem; ', 'label' => __('2.0')],
        ['value' => 'font-size: 2.1rem; ', 'label' => __('2.1')],
        ['value' => 'font-size: 2.2rem; ', 'label' => __('2.2')],
        ['value' => 'font-size: 2.3rem; ', 'label' => __('2.3')],
        ['value' => 'font-size: 2.4rem; ', 'label' => __('2.4')],
        ['value' => 'font-size: 2.5rem; ', 'label' => __('2.5')],
        ['value' => 'font-size: 2.6rem; ', 'label' => __('2.6')],
        ['value' => 'font-size: 2.7rem; ', 'label' => __('2.7')],
        ['value' => 'font-size: 2.8rem; ', 'label' => __('2.8')]
    ];
  }
}
