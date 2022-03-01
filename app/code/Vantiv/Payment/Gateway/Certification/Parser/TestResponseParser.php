<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Certification\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Certification test response wrapper implementation.
 */
class TestResponseParser extends AbstractResponseParser
{

    /**
     * Xpath prefix field
     *
     * @var
     */
    private $pathPrefix;

    /**
     * Get xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     * Set xpath prefix
     *
     * @param string $pathPrefix
     * @return void
     */
    public function setPathPrefix($pathPrefix)
    {
        $this->pathPrefix = $pathPrefix;
    }
}
