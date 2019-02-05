<?php
namespace Freshrelevance\Digitaldatalayer\Model\Config\Source;

class ExposureType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [['label' => 'Simple', 'value' => '0'],
            ['label' => 'Full', 'value' => '1']];
    }
}
