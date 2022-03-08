<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Token;

/**
 * Certification test model
 */
class EcheckTokenTest extends AbstractTokenTest
{
    /**
     * Get feature ID.
     *
     * @return string
     */
    public function getId()
    {
        return 'vantiv_echeck_vault';
    }

    /**
     * Get "active" flag state.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->getConfig()->getValue('payment/vantiv_echeck_vault/' . self::ACTIVE);
    }

    /**
     * Get environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->getConfig()->getValue('payment/vantiv_echeck/' . self::ENVIRONMENT);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Register eCheck Token';
    }

    /**
     * Get registration request test data.
     *
     * @return array
     */
    protected function getRequestData()
    {
        $data = [
            '53' => [
                'orderId'    => '53',
                'accNum'     => '1099999998',
                'routingNum' => '011100012',
            ],
            '54' => [
                'orderId'    => '54',
                'accNum'     => '1022222102',
                'routingNum' => '1145_7895',
            ],
        ];

        return $data;
    }

    /**
     * Get registration response data.
     *
     * @return array
     */
    protected function getResponseData()
    {
        $data = [
            '53' => [
                'type'                => 'EC',
                'eCheckAccountSuffix' => '998',
                'response'            => '801',
                'message'             => 'Account number was successfully registered',
            ],
            '54' => [
                'response' => '900',
                'message'  => 'Invalid Bank Routing Number',
            ],
        ];

        return $data;
    }
}
