<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

/**
 * Certification test model
 */
class AuthorizationDuplicateAddOnTest extends AbstractSubscriptionsTest
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
    public function authorize(array $dataSet, $orderId)
    {
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
                        '<createAddOn>' .
                            '<addOnCode>' .
            $dataSet['recurringRequest']['subscription']['createAddOn1']['addOnCode'] . '</addOnCode>' .
                            '<name>' .
            $dataSet['recurringRequest']['subscription']['createAddOn1']['name'] . '</name>' .
                            '<amount>' .
            $dataSet['recurringRequest']['subscription']['createAddOn1']['amount'] . '</amount>' .
                            '<startDate>' .
            $dataSet['recurringRequest']['subscription']['createAddOn1']['startDate'] . '</startDate>' .
                            '<endDate>' .
            $dataSet['recurringRequest']['subscription']['createAddOn1']['endDate'] . '</endDate>' .
                        '</createAddOn>' .
                        '<createAddOn>' .
                            '<addOnCode>' .
            $dataSet['recurringRequest']['subscription']['createAddOn2']['addOnCode'] . '</addOnCode>' .
                            '<name>' .
            $dataSet['recurringRequest']['subscription']['createAddOn2']['name'] . '</name>' .
                            '<amount>' .
            $dataSet['recurringRequest']['subscription']['createAddOn2']['amount'] . '</amount>' .
                            '<startDate>' .
            $dataSet['recurringRequest']['subscription']['createAddOn2']['startDate'] . '</startDate>' .
                            '<endDate>' .
            $dataSet['recurringRequest']['subscription']['createAddOn2']['endDate'] . '</endDate>' .
                        '</createAddOn>' .
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
            'R8.1' => [
                'orderId' => 'R8.1',
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
                        'planCode' => 'R1.6',
                        'createAddOn1' => [
                            'addOnCode' => 'SSL_ADDON',
                            'name' => 'Additional Service',
                            'amount' => '1000',
                            'startDate' => '2013-08-30',
                            'endDate' => '2050-08-30',
                        ],
                        'createAddOn2' => [
                            'addOnCode' => 'SSL_ADDON',
                            'name' => 'Additional Service',
                            'amount' => '1000',
                            'startDate' => '2013-08-30',
                            'endDate' => '2050-08-30',
                        ],
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
            'R8.1' => [
                'recurringResponse/responseCode' => 477,
                'recurringResponse/responseMessage' => 'Duplicate add-on codes in request',
            ],
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test Response not found'));
        }

        return $data[$orderId];
    }
}
