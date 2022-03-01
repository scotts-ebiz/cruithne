<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification;

/**
 * Certification test model interface
 */
interface TestInterface
{
    /**
     * @var string
     */
    const PATH_PREFIX = '';

    /**
     * @var string
     */
    const ID = '';

    /**
     * @var string
     */
    const ACTIVE = 'active';

    /**
     * @var string
     */
    const NAME = 'title';

    /**
     * @var string
     */
    const ENVIRONMENT = 'environment';

    /**
     * Get "active" flag
     *
     * @return bool
     */
    public function getActive();

    /**
     * Get test name
     *
     * @return string
     */
    public function getName();

    /**
     * Get configured environment setting
     *
     * @return string
     */
    public function getEnvironment();

    /**
     * Run test
     *
     * @return mixed
     */
    public function execute(array $subject = []);
}
