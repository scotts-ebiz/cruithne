<?php
namespace Freshrelevance\Digitaldatalayer\Model\Config\Source;

class RedirectPages implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [['label' => 'cart', 'value' => '0'],
            ['label' => 'onepage', 'value' => '1'],
            ['label' => 'custom', 'value' => '2']];
    }
}
