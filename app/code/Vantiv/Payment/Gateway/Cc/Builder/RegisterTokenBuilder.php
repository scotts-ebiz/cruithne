<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use Vantiv\Payment\Gateway\Common\Builder\AbstractCustomRequestBuilder;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Renderer\RegisterTokenRenderer;

/**
 * Register token request builder class.
 */
class RegisterTokenBuilder extends AbstractCustomRequestBuilder
{
    /**
     * Payment configuration instance.
     *
     * @var VantivCcConfig
     */
    private $config = null;

    /**
     * @var RegisterTokenRenderer
     */
    private $registerTokenRenderer = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param VantivCcConfig $config
     */
    public function __construct(
        SubjectReader $reader,
        VantivCcConfig $config,
        RegisterTokenRenderer $registerTokenRenderer
    ) {
        parent::__construct($reader);

        $this->config = $config;
        $this->registerTokenRenderer = $registerTokenRenderer;
    }

    /**
     * Get payment configuration instance.
     *
     * @return VantivCcConfig
     */
    private function getConfig()
    {
        return $this->config;
    }

    /**
     * Build <registerTokenRequest> XML node.
     *
     * <registerTokenRequest reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <paypageRegistrationId>PAYPAGE_REGISTRATION_ID</paypageRegistrationId>
     * </registerTokenRequest>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        /*
         * Preapre request data.
         */
        $reportGroup = $this->getConfig()->getValue('report_group');
        $customerId = $this->getReader()->readPaymentToken($subject)->getCustomerId();
        $paypageRegistrationId = $this->getReader()->readPaypageRegistrationId($subject);
        $data = [
            'reportGroup' => $reportGroup,
            'customerId' => $customerId,
            'id' => $this->getId(),
            'paypageRegistrationId' => $paypageRegistrationId,
        ];
        $data += $this->getAuthenticationData($subject);

        return $this->registerTokenRenderer->render($data);
    }

    /**
     * Read API merchant ID.
     *
     * @param array $subject
     * @return string
     */
    protected function readMerchant(array $subject)
    {
        return $this->getConfig()->getValue('merchant_id');
    }

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    protected function readUsername(array $subject)
    {
        return $this->getConfig()->getValue('username');
    }

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    protected function readPassword(array $subject)
    {
        return $this->getConfig()->getValue('password');
    }
}
