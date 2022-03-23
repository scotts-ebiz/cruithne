<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Builder;

use Vantiv\Payment\Gateway\Common\SubjectReader;

/**
 * Vantiv XML request builder.
 *
 * @api
 */
abstract class AbstractCustomRequestBuilder extends AbstractLitleOnlineRequestBuilder
{
    /**
     * Subject reader instance.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     */
    public function __construct(SubjectReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get subject reader.
     *
     * @return SubjectReader
     */
    protected function getReader()
    {
        return $this->reader;
    }
}
