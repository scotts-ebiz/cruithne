<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

/**
 * Certification test model
 */
class AuthorizationAdditionalServiceTest extends AbstractSubscriptionsTest
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
                        '<createDiscount>' .
                            '<discountCode>' .
            $dataSet['recurringRequest']['subscription']['createDiscount']['discountCode'] . '</discountCode>' .
                            '<name>' .
            $dataSet['recurringRequest']['subscription']['createDiscount']['name'] . '</name>' .
                            '<amount>' .
            $dataSet['recurringRequest']['subscription']['createDiscount']['amount'] . '</amount>' .
                            '<startDate>' .
            $dataSet['recurringRequest']['subscription']['createDiscount']['startDate'] . '</startDate>' .
                            '<endDate>' .
            $dataSet['recurringRequest']['subscription']['createDiscount']['endDate'] . '</endDate>' .
                        '</createDiscount>' .
                        '<createAddOn>' .
                            '<addOnCode>' .
            $dataSet['recurringRequest']['subscription']['createAddOn']['addOnCode'] . '</addOnCode>' .
                            '<name>' .
            $dataSet['recurringRequest']['subscription']['createAddOn']['name'] . '</name>' .
                            '<amount>' .
            $dataSet['recurringRequest']['subscription']['createAddOn']['amount'] . '</amount>' .
                            '<startDate>' .
            $dataSet['recurringRequest']['subscription']['createAddOn']['startDate'] . '</startDate>' .
                            '<endDate>' .
            $dataSet['recurringRequest']['subscription']['createAddOn']['endDate'] . '</endDate>' .
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
            'R7.1' => [
                'orderId' => 'R7.1',
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
                        'createAddOn' => [
                            'addOnCode' => 'SSL_ADDON',
                            'name' => 'Additional Service',
                            'amount' => '1000',
                            'startDate' => '2013-08-30',
                            'endDate' => '2050-08-30',
                        ],
                        'createDiscount' => [
                            'discountCode' => 'PROMO_DISCOUNT',
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
            'R7.1' => [
                'recurringResponse/responseCode' => 470,
                'recurringResponse/responseMessage' => 'Approved - Recurring subscription created',
            ],
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test Response not found'));
        }

        return $data[$orderId];
    }
}
