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
abstract class AbstractPaymentRequestBuilder extends AbstractLitleOnlineRequestBuilder
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

    /**
     * Read API merchant ID.
     *
     * @param array $subject
     * @return string
     */
    protected function readMerchant(array $subject)
    {
        return $this->readConfigData($subject, 'merchant_id');
    }

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    protected function readUsername(array $subject)
    {
        return $this->readConfigData($subject, 'username');
    }

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    protected function readPassword(array $subject)
    {
        return $this->readConfigData($subject, 'password');
    }

    /**
     * Read payment method config data.
     *
     * @param array $subject
     * @param string $key
     * @return mixed
     */
    private function readConfigData(array $subject, $key)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();
        return $method->getConfigData($key);
    }
}
