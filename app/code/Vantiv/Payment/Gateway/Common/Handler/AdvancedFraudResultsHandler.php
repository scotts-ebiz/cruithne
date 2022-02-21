<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Handler;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;
use Vantiv\Payment\Model\Config\Source\AdvancedFraudAction;

/**
 * Handle <advancedFraudResults> response data.
 */
class AdvancedFraudResultsHandler
{
    /**
     * Subject reader.
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
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Set credit card data into payment.
     *
     * @throws CommandException
     * @param array $subject
     * @param Parser $parser
     * @return boolean
     */
    public function handle(array $subject, Parser $parser)
    {
        $payment = $this->getReader()->readPayment($subject);
        $result = true;

        if ($payment->getMethodInstance()->getConfigData('advanced_fraud_is_active')) {
            $status = $parser->getDeviceReviewStatus();
            if (!empty($status)) {
                $field = sprintf('advanced_fraud_results_%s_action', $status);
                $action = $payment->getMethodInstance()->getConfigData($field);

                if ($action === AdvancedFraudAction::REJECT) {
                    $result = false;
                } elseif ($action === AdvancedFraudAction::REVIEW) {
                    $payment->setIsFraudDetected(true);
                }

                $score = $parser->getDeviceReputationScore();

                $payment->setAdditionalInformation('device_review_status', $status);
                $payment->setAdditionalInformation('device_reputation_score', $score);
            }
        }

        return $result;
    }
}
