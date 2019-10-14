<?php
namespace SMG\Vantiv\Model\Config\Source;

class ListMode implements \Magento\Framework\Option\ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'default', 'label' => __('Default')],
    ['value' => 'xml', 'label' => __('XML')]
  ];
 }
}