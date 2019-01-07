<?php

namespace SMG\ProductCalloutVideo\Model\Source;

class HeadlineTextStyles implements \Magento\Framework\Option\ArrayInterface
{
  public function toOptionArray()
  {
    return [['value' => 'font-style:italic; ', 'label' => __('Italic')], ['value' => 'font-weight:700; ', 'label' => __('Bold')], ['value' =>
      'text-decoration:underline; ', 'label' => __('Underline')]];
  }
}
