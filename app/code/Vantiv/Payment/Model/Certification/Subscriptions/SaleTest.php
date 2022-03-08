<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

/**
 * Certification test model
 */
class SaleTest extends AbstractSubscriptionsTest
{
    /**
     * @inheritdoc
     */
    public function execute(array $subject = [])
    {
        foreach ($this->getRequestData() as $orderId => $dataSet) {
            $this->sale($dataSet, $orderId);
        }
    }

    /**
     * @param array $dataSet
     * @param string $orderId
     */
    private function sale($dataSet, $orderId)
    {
        $amount = '';
        if (isset($dataSet['recurringRequest']['subscription']['amount'])) {
            $amount = '<amount>' . $dataSet['recurringRequest']['subscription']['amount'] . '</amount>';
        }
        $requestXml = $this->createAuthenticationWrapper(
            '<sale reportGroup="' . $dataSet['reportGroup'] . '">' .
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
                        '<startDate>' . $dataSet['recurringRequest']['subscription']['startDate'] . '</startDate>' .
                        $amount .
                    '</subscription>' .
                '</recurringRequest>' .
            '</sale>'
        );

        $parser = $this->getTestResponse($requestXml, 'saleResponse');
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
            'R2.1' => [
                'orderId' => 'R2.1',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => 1000,
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
            'R3.1' => [
                'orderId' => 'R3.1',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => 4000,
                'card' => [
                    'type' => $defaultData['card1']['type'],
                    'number' => $defaultData['card1']['number'],
                    'expDate' => $defaultData['card1']['expDate'],
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.1',
                        'startDate' => $defaultData['dateInFuture1'],
                        'amount' => 4000,
                    ]
                ],
            ]
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
            'R2.1' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ],
            'R3.1' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ]
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test response not found'));
        }

        return $data[$orderId];
    }
}
