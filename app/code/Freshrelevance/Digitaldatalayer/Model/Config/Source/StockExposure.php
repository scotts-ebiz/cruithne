<?php
namespace Freshrelevance\Digitaldatalayer\Model\Config\Source;

class StockExposure implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [['label' => 'Don\'t expose stock', 'value' => '0'],
            ['label' => 'Only Expose In or Out of stock', 'value' => '1'],
            ['label' => 'Expose actual stock level', 'value' => '2']];
    }
}
