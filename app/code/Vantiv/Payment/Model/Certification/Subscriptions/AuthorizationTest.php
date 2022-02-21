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
class AuthorizationTest extends AbstractSubscriptionsTest
{
    /**
     * @inheritdoc
     */
    public function execute(array $subject = [])
    {
        foreach ($this->getRequestData() as $orderId => $dataSet) {
            $this->authorize($dataSet, $orderId);
        }
    }

    /**
     * @param array $dataSet
     * @param string $orderId
     * @return string
     */
    private function authorize(array $dataSet, $orderId)
    {
        $numberOfPayments = '';
        if (isset($dataSet['recurringRequest']['subscription']['numberOfPayments'])) {
            $numberOfPayments = '<numberOfPayments>' .
                $dataSet['recurringRequest']['subscription']['numberOfPayments'] . '</numberOfPayments>';
        }

        $startDate = '';
        if (isset($dataSet['recurringRequest']['subscription']['startDate'])) {
            $startDate = '<startDate>' . $dataSet['recurringRequest']['subscription']['startDate'] . '</startDate>';
        }

        $subscriptionAmount = '';
        if (isset($dataSet['recurringRequest']['subscription']['amount'])) {
            $subscriptionAmount = '<amount>' . $dataSet['recurringRequest']['subscription']['amount'] . '</amount>';
        }

        $requestXml = $this->createAuthenticationWrapper(
            '<authorization reportGroup="' . $dataSet['reportGroup'] . '">' .
                '<orderId>' . $dataSet['orderId'] . '</orderId>' .
                '<amount>' . $dataSet['amount'] . '</amount>' .
                '<orderSource>' . $dataSet['orderSource'] . '</orderSource>' .
                '<card>' .
                    '<type>' . $dataSet['card']['type'] . '</type>' .
                    '<number>' . $dataSet['card']['number'] . '</number>' .
                    '<expDate>' . $dataSet['card']['expDate'] . '</expDate>' .
                '</card>' .
                '<recurringRequest>' .
                    '<subscription>' .
                        '<planCode>' .
            $this->getPlanCode($dataSet['recurringRequest']['subscription']['planCode']) . '</planCode>' .
                        $numberOfPayments .
                        $startDate .
                        $subscriptionAmount .
                    '</subscription>' .
                '</recurringRequest>' .
            '</authorization>'
        );

        $parser = $this->getTestResponse($requestXml, 'authorizationResponse');
        $this->setSubscriptionId($orderId, $parser->getValue('recurringResponse/subscriptionId'));

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
        $defaultData = $this->getDefaultRequestData();
        return [
            'R2.2' => [
                'orderId' => 'R2.2',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => '000',
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.1',
                        'startDate' => $defaultData['dateInFuture1'],
                    ]
                ],
            ],
            'R3.2' => [
                'orderId' => 'R3.2',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => $defaultData['amount'],
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.3',
                        'startDate' => $defaultData['dateInFuture1'],
                        'amount' => 4000,
                        'numberOfPayments' => 6,
                    ]
                ],
            ],
            'R4.1' => [
                'orderId' => 'R4.1',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => $defaultData['amount'],
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.4',
                        'startDate' => $defaultData['dateInFuture1'],
                        'amount' => 4000,
                    ]
                ],
            ],
            'R5.1' => [
                'orderId' => 'R5.1',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => $defaultData['amount'],
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.5',
                        'startDate' => $defaultData['dateInFuture1'],
                        'numberOfPayments' => 6,
                    ]
                ],
            ],
            'R6.1' => [
                'orderId' => 'R6.1',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => $defaultData['amount'],
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'INVALID_PLAN',
                    ]
                ],
            ],
            'R6.3' => [
                'orderId' => 'R6.3',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => $defaultData['amount'],
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.1',
                        'startDate' => $defaultData['dateInPast'],
                    ]
                ],
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
            'R2.2' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ],
            'R3.2' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ],
            'R4.1' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ],
            'R5.1' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ],
            'R6.1' => [
                'recurringResponse/responseCode' => 472,
                'recurringResponse/responseMessage' => 'Invalid plan code',
            ],
            'R6.3' => [
                'recurringResponse/responseCode' => 482,
                'recurringResponse/responseMessage' => 'Invalid start date',
            ],
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test Response not found'));
        }

        return $data[$orderId];
    }
}
