<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\OfflineShipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Method
 * @package SMG\OfflineShipping\Model\Config\Source
 */
class Method implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getAvailableMethods() as $code => $title) {
            $options[] = ['value' => $code, 'label' => $title];
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getAvailableMethods()
    {
        return [
            'fedex-nextday' => __('Fedex Next Day'),
            'fedex-2ndday' => __('Fedex 2nd Day'),
        ];
    }
}
