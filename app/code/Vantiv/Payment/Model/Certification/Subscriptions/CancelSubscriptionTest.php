<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

/**
 * Certification test model
 */
class CancelSubscriptionTest extends AbstractSubscriptionsTest
{
    /**
     * @inheritdoc
     */
    public function execute(array $subject = [])
    {
        foreach ($this->getRequestData() as $orderId => $dataSet) {
            $this->cancelSubscription($dataSet, $orderId);
        }
    }

    /**
     * @param array $dataSet
     * @param string $orderId
     */
    private function cancelSubscription($dataSet, $orderId)
    {
        $subscriptionId = $this->getSubscriptionIdByOrderId($dataSet['subscriptionId']);
        $requestXml = $this->createAuthenticationWrapper(
            '<cancelSubscription>' .
                '<subscriptionId>' . $subscriptionId . '</subscriptionId>' .
            '</cancelSubscription>'
        );

        $parser = $this->getTestResponse($requestXml, 'cancelSubscriptionResponse');

        $status = $status = $this->validateResponse($parser, $this->getResponseData($subscriptionId));
        $this->saveResultData(
            $status,
            $orderId,
            $parser->getLitleTxnId(),
            $requestXml,
            $parser->toXml()
        );
    }

    /**
     * @return array
     */
    private function getRequestData()
    {
        return [
            'R10.1' => [
                'subscriptionId' => 'R7.1',
            ],
        ];
    }

    /**
     * @param integer $subscriptionId
     * @return array
     */
    private function getResponseData($subscriptionId)
    {
        return [
            'subscriptionId' => $subscriptionId,
            'response' => '000',
            'message' => 'Approved',
        ];
    }
}
