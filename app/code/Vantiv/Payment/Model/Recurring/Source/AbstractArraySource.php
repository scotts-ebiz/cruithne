<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Source;

abstract class AbstractArraySource implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritdoc
     */
    abstract public function toOptionArray();

    /**
     * @return array
     */
    public function toOptionHash()
    {
        $result = [];
        foreach ($this->toOptionArray() as $item) {
            $result[$item['value']] = $item['label'];
        }

        return $result;
    }
}
