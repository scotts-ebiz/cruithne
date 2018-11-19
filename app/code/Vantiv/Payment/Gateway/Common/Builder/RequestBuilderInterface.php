<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Builder;

/**
 * Vantiv XML request builder interface.
 *
 * @api
 */
interface RequestBuilderInterface
{
    /**
     * Build XML document.
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject);
}
