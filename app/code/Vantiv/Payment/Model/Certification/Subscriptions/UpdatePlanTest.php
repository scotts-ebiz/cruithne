<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

/**
 * Certification test model
 */
class UpdatePlanTest extends AbstractSubscriptionsTest
{
    /**
     * @inheritdoc
     */
    public function execute(array $subject = [])
    {
        foreach ($this->getRequestData() as $orderId => $dataSet) {
            $this->updatePlan($dataSet, $orderId);
        }
    }

    /**
     * @param array $dataSet
     * @param string $orderId
     */
    private function updatePlan($dataSet, $orderId)
    {
        $requestXml = $this->createAuthenticationWrapper(
            '<updatePlan>' .
                '<planCode>' . $this->getPlanCode($dataSet['planCode']) . '</planCode>' .
                '<active>' . $dataSet['active'] . '</active>' .
            '</updatePlan>'
        );

        $parser = $this->getTestResponse($requestXml, 'updatePlanResponse');

        $status = $status = $this->validateResponse($parser, $this->getResponseData());
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
            'R11.1' => [
                'planCode' => 'R1.2',
                'active' => 'false',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getResponseData()
    {
        return [
            'response' => '000',
            'message' => 'Approved',
        ];
    }
}
