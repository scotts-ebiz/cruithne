<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

/**
 * Suspect Issuer Action options Source Model.
 */
class SuspectIssuerAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Suspect Issuer Action code for 'accept' option.
     */
    const ACCEPT_CODE = 'accept';

    /**
     * Suspect Issuer Action code for 'reject' option.
     */
    const REJECT_CODE = 'reject';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ACCEPT_CODE, 'label' => __('Accept')],
            ['value' => self::REJECT_CODE, 'label' => __('Reject')]
        ];
    }
}
