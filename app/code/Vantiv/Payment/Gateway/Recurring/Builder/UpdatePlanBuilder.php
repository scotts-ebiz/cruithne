<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring\Builder;

use XMLWriter;

class UpdatePlanBuilder extends AbstractSubscriptionRequestBuilder
{
    /**
     * Build <updatePlan> XML node.
     *
     * <updatePlan>
     *     <planCode>Reference_Code</planCode>
     *     <active>false</active>
     * </updatePlan>
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
        $writer->startElement('updatePlan');

        if ($plan->hasData('code')) {
            $writer->writeElement('planCode', $plan->getCode());
        }

        if ($plan->hasData('active')) {
            $writer->writeElement('active', $plan->getActive() ? 'true' : 'false');
        }

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
