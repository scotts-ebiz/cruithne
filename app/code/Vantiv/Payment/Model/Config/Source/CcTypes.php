<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

/**
 * Payment CC Types Source Model.
 */
class CcTypes extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * Allowed credit card types.
     *
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return ['AE', 'MC', 'VI', 'DI'];
    }
}
