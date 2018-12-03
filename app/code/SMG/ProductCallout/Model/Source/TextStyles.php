<?php

namespace SMG\ProductCallout\Model\Source;

class TextStyles implements \Magento\Framework\Option\ArrayInterface
{
  public function toOptionArray()
  {
    return [['value' => 'text-style:italic; ', 'label' => __('Italic')], ['value' => 'font-weight:700; ', 'label' => __('Bold')], ['value' =>
      'text-decoration:underline; ', 'label' => __('Underline')]];
  }
}
