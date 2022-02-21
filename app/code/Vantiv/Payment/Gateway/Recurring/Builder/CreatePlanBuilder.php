<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring\Builder;

use XMLWriter;

class CreatePlanBuilder extends AbstractSubscriptionRequestBuilder
{
    /**
     * Build <createPlan> XML node.
     *
     * <createPlan>
     *     <planCode>Gold12</planCode>
     *     <name>Gold_Monthly</name>
     *     <description>Gold Level with Monthly Payments</description>
     *     <intervalType>MONTHLY</intervalType>
     *     <amount>5000</amount>
     *     <numberOfPayments>4</numberOfPayments>
     *     <trialNumberOfIntervals>1</trialNumberOfIntervals>
     *     <trialIntervalType>MONTH</trialIntervalType>
     *     <active>true</active>
     * </createPlan>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $plan = $this->getReader()->readPlan($subject);

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('createPlan');

        if ($plan->hasData('code')) {
            $writer->writeElement('planCode', $plan->getCode());
        }

        if ($plan->hasData('name')) {
            $writer->writeElement('name', $plan->getName());
        }

        if ($plan->hasData('description')) {
            $writer->writeElement('description', $plan->getDescription());
        }

        if ($plan->hasData('interval')) {
            $writer->writeElement('intervalType', $plan->getInterval());
        }

        if ($plan->hasData('interval_amount')) {
            $amount = number_format(($plan->getIntervalAmount() * 100), 0, null, '');
            $writer->writeElement('amount', $amount);
        }

        if ($plan->hasData('number_of_payments')) {
            $writer->writeElement('numberOfPayments', $plan->getNumberOfPayments());
        }

        if ($plan->hasData('number_of_trial_intervals')) {
            $writer->writeElement('trialNumberOfIntervals', $plan->getNumberOfTrialIntervals());
        }

        if ($plan->hasData('trial_interval')) {
            $writer->writeElement('trialIntervalType', $plan->getTrialInterval());
        }

        if ($plan->hasData('active')) {
            $writer->writeElement('active', $plan->getActive() ? 'true' : 'false');
        }

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
