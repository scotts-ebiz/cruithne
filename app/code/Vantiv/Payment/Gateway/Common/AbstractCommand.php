<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common;

use Vantiv\Payment\Model\Config\Source\VantivEnvironment;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Class Vantiv Gateway AbstractCommand
 *
 * @api
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @throws CommandException
     * @return void
     */
    abstract public function execute(array $subject);

    /**
     * Get API endpoint URL.
     *
     * @param string $environment
     * @return string
     */
    protected function getUrlByEnvironment($environment)
    {
        $url = '';

        /**
         * API endpoints URL map.
         *
         * @var array $map
         */
        $map = [
            VantivEnvironment::SANDBOX => 'https://www.testvantivcnp.com/sandbox/communicator/online',
            VantivEnvironment::PRELIVE => 'https://payments.vantivprelive.com/vap/communicator/online',
            VantivEnvironment::TRANSACT_PRELIVE => 'https://transact.vantivprelive.com/vap/communicator/online',
            VantivEnvironment::POSTLIVE => 'https://payments.vantivpostlive.com/vap/communicator/online',
            VantivEnvironment::TRANSACT_POSTLIVE => 'https://transact.vantivpostlive.com/vap/communicator/online',
            VantivEnvironment::PRODUCTION => 'https://payments.vantivcnp.com/vap/communicator/online',
            VantivEnvironment::TRANSACT_PRODUCTION => 'https://transact.vantivcnp.com/vap/communicator/online',
        ];

        if (array_key_exists($environment, $map)) {
            $url = $map[$environment];
        } else {
            throw new \InvalidArgumentException('Invalid environment.');
        }

        return $url;
    }
}
