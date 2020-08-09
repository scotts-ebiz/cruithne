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
        $options[] = [
            'value' => 'flatrate',
            'label' => __('Native Flatrate Shipping')
        ];
        return $options;
    }

    /**
     * @return array
     */
    public function getAvailableMethods()
    {
        return [
            'fedex-nextday' => __('FedEx Next Day, 1 day'),
            'fedex-2ndday' => __('FedEx 2nd Day, 2 days'),
            'flat-rate-shipping' => __('Flat Rate, 3-4 days'),
            'customer-pickup' => __('Customer Pick-up')
        ];
    }
}