<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Token;

/**
 * Certification test model
 */
class CcTokenTest extends AbstractTokenTest
{
    /**
     * Get feature ID.
     *
     * @return string
     */
    public function getId()
    {
        return 'vantiv_cc_vault';
    }

    /**
     * Get "active" flag state.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->getConfig()->getValue('payment/vantiv_cc_vault/' . self::ACTIVE);
    }

    /**
     * Get environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->getConfig()->getValue('payment/vantiv_cc/' . self::ENVIRONMENT);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Register Card Token';
    }

    /**
     * Get registration request test data.
     *
     * @return array
     */
    protected function getRequestData()
    {
        $data = [
            '50' => [
                'orderId'       => '50',
                'accountNumber' => '4457119922390123',
            ],
            '51' => [
                'orderId'       => '51',
                'accountNumber' => '4457119999999999',
            ],
            '52' => [
                'orderId'       => '52',
                'accountNumber' => '4457119922390123',
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
            '50' => [
                'tokenBin'   => '445711',
                'tokenType'  => 'VI',
                'response'   => '801',
                'message'    => 'Account number was successfully registered',
            ],
            '51' => [
                'response' => '820',
                'message'  => 'Credit card number was invalid',
            ],
            '52' => [
                'tokenBin'   => '445711',
                'tokenType'  => 'VI',
                'response'   => '802',
                'message'    => 'Account number was previously registered',
            ],
        ];

        return $data;
    }
}
