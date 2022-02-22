<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Subscriptions;

/**
 * Certification test model
 */
class AuthorizationCcTest extends AbstractSubscriptionsTest
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
            'R9.1' => [
                'orderId' => 'R9.1',
                'reportGroup' => $defaultData['reportGroup'],
                'orderSource' => $defaultData['orderSource'],
                'amount' => $defaultData['amount'],
                'card' => [
                    'type' => 'MC',
                    'number' => '5112010100000002',
                    'expDate' => '0714',
                ],
                'recurringRequest' => [
                    'subscription' => [
                        'planCode' => 'R1.6',
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
            'R9.1' => [
                'recurringResponse/responseCode' => 471,
                'recurringResponse/responseMessage' => 'Parent transaction declined - Recurring subscription not created',
            ],
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test Response not found'));
        }

        return $data[$orderId];
    }
}
