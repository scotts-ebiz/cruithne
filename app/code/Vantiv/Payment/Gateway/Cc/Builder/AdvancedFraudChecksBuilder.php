<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\RequestBuilderInterface;
use Vantiv\Payment\Model\Session;

/**
 * Advanced fraud checks XML node builder.
 */
class AdvancedFraudChecksBuilder implements RequestBuilderInterface
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Vantiv session instance.
     *
     * @var Session
     */
    private $session = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param Session $session
     */
    public function __construct(SubjectReader $reader, Session $session)
    {
        $this->reader = $reader;
        $this->session = $session;
    }

    /**
     * Get SubjectReader.
     *
     * @return SubjectReader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Private session getter.
     *
     * @return Session
     */
    private function getSession()
    {
        return $this->session;
    }

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
    public function build(array $subject)
    {
        $isActive = $this->getReader()
            ->readPayment($subject)
            ->getMethodInstance()
            ->getConfigData('advanced_fraud_is_active');

        $xml = '';

        if ($isActive) {
            /*
             * Prepare document variables.
             */
            $sessionId = $this->getSession()->getUniqueSessionId();

            /*
             * Generate document.
             */
            $writer = new XMLWriter();
            $writer->openMemory();
            $writer->setIndent(true);
            $writer->setIndentString(str_repeat(' ', 4));
            $writer->startElement('advancedFraudChecks');
            {
                $writer->startElement('threatMetrixSessionId');
                $writer->text($sessionId);
                $writer->endElement();
            }
            $writer->endElement();
            $xml = $writer->outputMemory();
        }

        return $xml;
    }

    /**
     * @param array $subject
     * @return array
     */
    public function extract(array $subject)
    {
        $data = [];

        $isActive = $this->getReader()
            ->readPayment($subject)
            ->getMethodInstance()
            ->getConfigData('advanced_fraud_is_active');
        if ($isActive) {
            $data['threatMetrixSessionId'] =  $this->session->getUniqueSessionId();
        }

        return $data;
    }
}
