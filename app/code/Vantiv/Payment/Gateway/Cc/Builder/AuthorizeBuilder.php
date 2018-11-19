<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\BillToAddressBuilder;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;
use Vantiv\Payment\Gateway\Common\Builder\EnhancedDataBuilder;
use Vantiv\Payment\Gateway\Recurring\Builder\RecurringRequestBuilder;
use Vantiv\Payment\Gateway\Common\Renderer\CcAuthorizationRenderer;

/**
 * Class AuthorizeBuilder
 */
class AuthorizeBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * @var PaypageBuilder
     */
    private $paypageBuilder = null;

    /**
     * Billing address builder.
     *
     * @var BillToAddressBuilder
     */
    private $billToAddressBuilder = null;

    /**
     * Advanced fraud builder.
     *
     * @var AdvancedFraudChecksBuilder
     */
    private $advancedFraudChecksBuilder = null;

    /**
     * Enhanced data builder
     *
     * @var EnhancedDataBuilder
     */
    private $enhancedDataBuilder = null;

    /**
     * Recurring/Subscription request builder
     *
     * @var RecurringRequestBuilder
     */
    private $recurringRequestBuilder;

    /**
     * @var CcAuthorizationRenderer
     */
    private $ccAuthorizationRenderer;

    /**
     * Constructor
     *
     * @param SubjectReader $reader
     * @param PaypageBuilder $paypageBuilder
     * @param BillToAddressBuilder $billToAddressBuilder
     * @param AdvancedFraudChecksBuilder $advancedFraudChecksBuilder
     * @param EnhancedDataBuilder $enhancedDataBuilder
     * @param RecurringRequestBuilder $recurringRequestBuilder
     * @param CcAuthorizationRenderer $ccAuthorizationRenderer
     */
    public function __construct(
        SubjectReader $reader,
        PaypageBuilder $paypageBuilder,
        BillToAddressBuilder $billToAddressBuilder,
        AdvancedFraudChecksBuilder $advancedFraudChecksBuilder,
        EnhancedDataBuilder $enhancedDataBuilder,
        RecurringRequestBuilder $recurringRequestBuilder,
        CcAuthorizationRenderer $ccAuthorizationRenderer
    ) {
        parent::__construct($reader);

        $this->paypageBuilder = $paypageBuilder;
        $this->billToAddressBuilder = $billToAddressBuilder;
        $this->advancedFraudChecksBuilder = $advancedFraudChecksBuilder;
        $this->enhancedDataBuilder = $enhancedDataBuilder;
        $this->recurringRequestBuilder = $recurringRequestBuilder;
        $this->ccAuthorizationRenderer = $ccAuthorizationRenderer;
    }

    /**
     * Get paypage builder.
     *
     * @return PaypageBuilder
     */
    private function getPaypageBuilder()
    {
        return $this->paypageBuilder;
    }

    /**
     * Get billing address builder.
     *
     * @return BillToAddressBuilder
     */
    private function getBillToAddressBuilder()
    {
        return $this->billToAddressBuilder;
    }

    /**
     * Get advanced fraud builder.
     *
     * @return AdvancedFraudChecksBuilder
     */
    private function getAdvancedFraudChecksBuilder()
    {
        return $this->advancedFraudChecksBuilder;
    }

    /**
     * Get enhanced data builder
     *
     * @return EnhancedDataBuilder
     */
    private function getEnhancedDataBuilder()
    {
        return $this->enhancedDataBuilder;
    }

    /**
     * Get recurring/subscription request builder
     *
     * @return RecurringRequestBuilder
     */
    private function getRecurringRequestBuilder()
    {
        return $this->recurringRequestBuilder;
    }

    /**
     * Build <authorization> XML node.
     *
     * <authorization reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <PAYPAGE_NODE/>
     *     <enhancedData />
     *     <recurringRequest />
     * </authorization>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        /*
         * Prepare document variables.
         */
        $orderAdapter = $this->getReader()->readOrderAdapter($subject);

        $data = [
            'reportGroup' => $method->getConfigData('report_group'),
            'customerId' => $orderAdapter->getCustomerId(),
            'id' => $this->getId(),
            'orderId' => $orderAdapter->getOrderIncrementId(),
            'amount' => $this->getReader()->readAmount($subject) * 100,
            'orderSource' => $method->getConfigData('order_source'),
        ];
        $data += $this->getAuthenticationData($subject);
        $data += $this->getBillToAddressBuilder()->extract($subject);
        $data += $this->getAdvancedFraudChecksBuilder()->extract($subject);
        $data += $this->getPaypageBuilder()->extract($subject);
        $data += $this->getEnhancedDataBuilder()->extract($subject);
        $data += $this->getRecurringRequestBuilder()->extract($subject);

        return $this->ccAuthorizationRenderer->render($data);
    }
}
