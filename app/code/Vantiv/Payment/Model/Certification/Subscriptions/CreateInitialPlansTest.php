<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;

/**
 * Certification test model
 */
class CreateInitialPlansTest extends AbstractSubscriptionsTest
{
    /**
     * @inheritdoc
     */
    public function execute(array $subject = [])
    {
        foreach ($this->getRequestData() as $orderId => $dataSet) {
            $this->createSubscription($dataSet, $orderId);
        }
    }

    /**
     * @param array $dataSet
     * @param string $orderId
     * @return void
     */
    private function createSubscription(array $dataSet, $orderId)
    {
        $numberOfPayments = '';
        if (isset($dataSet['numberOfPayments'])) {
            $numberOfPayments = '<numberOfPayments>' . $dataSet['numberOfPayments'] . '</numberOfPayments>';
        }
        $requestXml = $this->createAuthenticationWrapper(
            '<createPlan>' .
                '<planCode>' . $this->getPlanCode($dataSet['planCode']) . '</planCode>' .
                '<name>' . $dataSet['name'] . '</name>' .
                '<description>' . $dataSet['description'] . '</description>' .
                '<intervalType>' . $dataSet['intervalType'] . '</intervalType>' .
                '<amount>' . $dataSet['amount'] . '</amount>' .
                $numberOfPayments .
            '</createPlan>'
        );

        $parser = $this->getTestResponse($requestXml, 'createPlanResponse');

        $status = $this->validateResponse($parser, $this->getResponseData($orderId));
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
            'R1.1' => [
                'planCode' => 'R1.1',
                'name' => 'R1.1',
                'description' => 'Basic monthly plan',
                'intervalType' => 'MONTHLY',
                'amount' => 5000,
                'numberOfPayments' => 12,
            ],
            'R1.2' => [
                'planCode' => 'R1.2',
                'name' => 'R1.2',
                'description' => 'Premium monthly plan',
                'intervalType' => 'MONTHLY',
                'amount' => 7000,
                'numberOfPayments' => 12,
            ],
            'R1.3' => [
                'planCode' => 'R1.3',
                'name' => 'R1.3',
                'description' => 'An annual plan',
                'intervalType' => 'ANNUAL',
                'amount' => 14000,
                'numberOfPayments' => 12,
            ],
            'R1.4' => [
                'planCode' => 'R1.4',
                'name' => 'R1.4',
                'description' => 'A monthly plan with trial period',
                'intervalType' => 'MONTHLY',
                'amount' => 5000,
                'numberOfPayments' => 2,
            ],
            'R1.5' => [
                'planCode' => 'R1.5',
                'name' => 'R1.5',
                'description' => 'A semi-annual plan',
                'intervalType' => 'SEMIANNUAL',
                'amount' => 7000,
            ],
            'R1.6' => [
                'planCode' => 'R1.6',
                'name' => 'R1.6',
                'description' => 'A plan with Add Ons and Discounts',
                'intervalType' => 'MONTHLY',
                'amount' => 5000,
            ],
            'R1.7' => [
                'planCode' => 'R1.3',
                'name' => 'R1.3',
                'description' => 'An annual plan',
                'intervalType' => 'ANNUAL',
                'amount' => 5000,
            ],
        ];
    }

    /**
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getResponseData($orderId)
    {
        $data = [
            'R1.1' => [
                'response' => '000',
                'message' => 'Approved',
                'planCode' => $this->getPlanCode('R1.1'),
            ],
            'R1.2' => [
                'response' => '000',
                'message' => 'Approved',
                'planCode' => $this->getPlanCode('R1.2'),
            ],
            'R1.3' => [
                'response' => '000',
                'message' => 'Approved',
                'planCode' => $this->getPlanCode('R1.3'),
            ],
            'R1.4' => [
                'response' => '000',
                'message' => 'Approved',
                'planCode' => $this->getPlanCode('R1.4'),
            ],
            'R1.5' => [
                'response' => '000',
                'message' => 'Approved',
                'planCode' => $this->getPlanCode('R1.5'),
            ],
            'R1.6' => [
                'response' => '000',
                'message' => 'Approved',
                'planCode' => $this->getPlanCode('R1.6'),
            ],
            'R1.7' => [
                'response' => 487,
                'message' => 'Plan code already exists',
                'planCode' => $this->getPlanCode('R1.3'),
            ],
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test response not found'));
        }

        return $data[$orderId];
    }
}
