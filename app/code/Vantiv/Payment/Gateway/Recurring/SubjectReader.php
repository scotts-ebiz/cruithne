<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring;

use Vantiv\Payment\Gateway\Common\SubjectReader as CommonSubjectReader;

/**
 * Subject reader.
 */
class SubjectReader extends CommonSubjectReader
{
    /**
     * Extract planDataObject from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Vantiv\Payment\Model\Recurring\Plan
     */
    public function readPlan(array $subject)
    {
        $planDataObject = null;

        if (array_key_exists('plan', $subject)) {
            $planDataObject = $subject['plan'];
        } else {
            throw new \InvalidArgumentException('planDataObject is not set.');
        }

        return $planDataObject;
    }

    /**
     * Extract subscriptionDataObject from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Vantiv\Payment\Model\Recurring\Subscription
     */
    public function readSubscription(array $subject)
    {
        $subscriptionDataObject = null;

        if (array_key_exists('subscription', $subject)) {
            $subscriptionDataObject = $subject['subscription'];
        } else {
            throw new \InvalidArgumentException('subscriptionDataObject is not set.');
        }

        return $subscriptionDataObject;
    }

    /**
     * Extract addonDataObject from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Vantiv\Payment\Model\Recurring\Subscription\Addon
     */
    public function readAddon(array $subject)
    {
        $subscriptionDataObject = null;

        if (array_key_exists('addon', $subject)) {
            $subscriptionDataObject = $subject['addon'];
        } else {
            throw new \InvalidArgumentException('addonDataObject is not set.');
        }

        return $subscriptionDataObject;
    }

    /**
     * Extract discountDataObject from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Vantiv\Payment\Model\Recurring\Subscription\Discount
     */
    public function readDiscount(array $subject)
    {
        $subscriptionDataObject = null;

        if (array_key_exists('discount', $subject)) {
            $subscriptionDataObject = $subject['discount'];
        } else {
            throw new \InvalidArgumentException('discountDataObject is not set.');
        }

        return $subscriptionDataObject;
    }

    /**
     * Extract subscriptionDataObject from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Vantiv\Payment\Model\Recurring\RecoveryTransaction
     */
    public function readRecoveryTransaction(array $subject)
    {
        $subscriptionDataObject = null;

        if (array_key_exists('recovery_transaction', $subject)) {
            $subscriptionDataObject = $subject['recovery_transaction'];
        } else {
            throw new \InvalidArgumentException('recoveryTransactionDataObject is not set.');
        }

        return $subscriptionDataObject;
    }
}
