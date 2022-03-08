<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Form;

/**
 * Class Form
 */
class Androidpay extends \Magento\Payment\Block\Form
{
    /**
     * Androidpay payment method doesn't have any user interface output.
     * So we need to create a stub for the rendering functionality.
     *
     * @return string
     */
    public function toHtml()
    {
        return '';
    }
}
