<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Advanced fraud checks XML node builder.
 */
class AdvancedFraudChecksRenderer extends AbstractRenderer
{
    /**
     * Build <advancedFraudChecks> XML node.
     *
     * <advancedFraudChecks>
     *     <threatMetrixSessionId>SESSION_ID</threatMetrixSessionId>
     * </advancedFraudChecks>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $xml = '';

        $threatMetrixSessionId = $this->readDataOrNull($subject, 'threatMetrixSessionId');
        if ($threatMetrixSessionId !== null) {
            /*
             * Generate document.
             */
            $writer = new XMLWriter();
            $writer->openMemory();
            $writer->setIndent(true);
            $writer->setIndentString(str_repeat(' ', 4));
            $writer->startElement('advancedFraudChecks');
            {
                $writer->writeElement('threatMetrixSessionId', $threatMetrixSessionId);
            }
            $writer->endElement();
            $xml = $writer->outputMemory();
        }

        return $xml;
    }
}
