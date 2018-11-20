<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common;

/**
 * Subject reader.
 *
 * @api
 */
class SubjectReader
{
    /**
     * Extract PaymentDataObject from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface
     */
    public function readPaymentDataObject(array $subject)
    {
        $paymentDataObject = null;

        if (array_key_exists('payment', $subject)) {
            $paymentDataObject = $subject['payment'];
        } else {
            throw new \InvalidArgumentException('PaymentDataObject is not set.');
        }

        return $paymentDataObject;
    }

    /**
     * Get amount submitted to current command.
     *
     * @param array $subject
     * @return float
     */
    public function readAmount(array $subject)
    {
        $amount = 0.00;

        if (array_key_exists('amount', $subject)) {
            $amount = $subject['amount'];
        }

        return $amount;
    }

    /**
     * Extract payment token from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function readPaymentToken(array $subject)
    {
        $token = null;

        if (array_key_exists('token', $subject)) {
            $token = $subject['token'];
        } else {
            throw new \InvalidArgumentException('Token is not set.');
        }

        return $token;
    }

    /**
     * Get Paypage registration ID submitted to current command.
     *
     * @param array $subject
     * @return string
     * @throws \InvalidArgumentException
     */
    public function readPaypageRegistrationId(array $subject)
    {
        $paypageRegistrationId = null;

        if (array_key_exists('paypage_registration_id', $subject)) {
            $paypageRegistrationId = $subject['paypage_registration_id'];
        } else {
            throw new \InvalidArgumentException('Parameter "paypage_registration_id" not found in subject.');
        }

        return $paypageRegistrationId;
    }

    /**
     * Get order model adapter instance.
     *
     * @param array $subject
     * @return \Magento\Payment\Gateway\Data\OrderAdapterInterface
     */
    public function readOrderAdapter(array $subject)
    {
        return $this->readPaymentDataObject($subject)->getOrder();
    }

    /**
     * Get payment information instance.
     *
     * @param array $subject
     * @return \Magento\Payment\Model\InfoInterface
     */
    public function readPayment(array $subject)
    {
        return $this->readPaymentDataObject($subject)->getPayment();
    }

    /**
     * Get transaction id submitted to current command
     *
     * @param array $subject
     * @return string
     */
    public function readTransactionId(array $subject)
    {
        $transactionId = '';

        if (array_key_exists('transaction_id', $subject)) {
            $transactionId = $subject['transaction_id'];
        }

        return $transactionId;
    }

    /**
     * Get void node submitted to current command
     *
     * @param array $subject
     * @return string
     */
    public function readVoidNode(array $subject)
    {
        $voidNode = '';

        if (array_key_exists('void_node', $subject)) {
            $voidNode = $subject['void_node'];
        }

        return $voidNode;
    }
}
