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
class UpdateSubscriptionTest extends AbstractSubscriptionsTest
{
    /**
     * @inheritdoc
     */
    public function execute(array $subject = [])
    {
        foreach ($this->getRequestData() as $orderId => $dataSet) {
            $this->updateSubscription($dataSet, $orderId);
        }
    }

    /**
     * @param array $dataSet
     * @param string $orderId
     * @return void
     */
    private function updateSubscription(array $dataSet, $orderId)
    {
        $billToAddress = $this->createBillToAddressNode($dataSet);
        $card = $this->createCardNode($dataSet);
        $addOn = $this->createAddOnNode($dataSet);
        $updateAddOn = $this->createUpdateAddOnNode($dataSet);
        $discount = $this->createDiscountNode($dataSet);
        $updateDiscount = $this->createUpdateDiscountNode($dataSet);

        $planCode = '';
        if (isset($dataSet['planCode'])) {
            $planCode = '<planCode>' . $this->getPlanCode($dataSet['planCode']) . '</planCode>';
        }

        $billingDate = '';
        if (isset($dataSet['billingDate'])) {
            $billingDate = '<billingDate>' . $dataSet['billingDate'] . '</billingDate>';
        }

        $requestXml = $this->createAuthenticationWrapper(
            '<updateSubscription>' .
                '<subscriptionId>' . $this->getSubscriptionIdByOrderId($dataSet['subscriptionId']) .
            '</subscriptionId>' .
                $planCode .
                $billingDate .
                $billToAddress .
                $card .
                $addOn .
                $updateAddOn .
                $discount .
                $updateDiscount .
            '</updateSubscription>'
        );

        $parser = $this->getTestResponse($requestXml, 'updateSubscriptionResponse');

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
            'R2.3' => [
                'subscriptionId' => 'R2.1',
                'planCode' => 'R1.2',
            ],
            'R3.3' => [
                'subscriptionId' => 'R3.1',
                'billingDate' => $defaultData['dateInFuture2'],
            ],
            'R4.2' => [
                'subscriptionId' => 'R4.1',
                'card' => [
                    'type' => 'VI',
                    'number' => '4457010000000009',
                    'expDate' => '1221',
                ],
            ],
            'R5.2' => [
                'subscriptionId' => 'R5.1',
                'billToAddress' => [
                    'name' => 'John',
                    'addressLine1' => '900 Chelmsford St.',
                    'city' => 'Lowell',
                    'state' => 'MA',
                    'zip' => '01781',
                    'country' => 'US',
                    'email' => 'john@gmail.com',
                    'phone' => '8559658965',
                ],
            ],
            'R5.3' => [
                'subscriptionId' => 'R5.1',
            ],
            'R5.4' => [
                'subscriptionId' => 'R5.1',
                'billingDate' => '2000-10-01',
            ],
            'R6.2' => [
                'subscriptionId' => '1111111111',
                'billToAddress' => [
                    'name' => 'John',
                    'addressLine1' => '900 Chelmsford St.',
                    'city' => 'Lowell',
                    'state' => 'MA',
                    'zip' => '01781',
                    'country' => 'US',
                    'email' => 'john@gmail.com',
                    'phone' => '8559658965',
                ],
            ],
            'R7.2' => [
                'subscriptionId' => 'R7.1',
                'createAddOn' => [
                    'addOnCode' => 'SSL_ADDON',
                    'name' => 'Additional Service',
                    'amount' => '1000',
                    'startDate' => '2013-08-30',
                    'endDate' => '2050-08-30',
                ],
            ],
            'R7.3' => [
                'subscriptionId' => 'R7.1',
                'createDiscount' => [
                    'discountCode' => 'PROMO_DISCOUNT',
                    'name' => 'Special Offer',
                    'amount' => '1000',
                    'startDate' => '2013-08-30',
                    'endDate' => '2050-08-30',
                ],
            ],
            'R7.4' => [
                'subscriptionId' => 'R7.1',
                'updateAddOn' => [
                    'addOnCode' => 'INVALID_ADDON_CODE',
                    'name' => 'Extra Features',
                    'amount' => '1000',
                ],
            ],
            'R7.5' => [
                'subscriptionId' => 'R7.1',
                'updateDiscount' => [
                    'discountCode' => 'INVALID_DISCOUNT_CODE',
                    'name' => 'Special Offer',
                    'amount' => '1000',
                    'startDate' => '2013-08-30',
                    'endDate' => '2050-08-30',
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
            'R2.3' => [
                'response' => '000',
                'message' => 'Approved',
            ],
            'R3.3' => [
                'response' => '000',
                'message' => 'Approved',
            ],
            'R4.2' => [
                'response' => '000',
                'message' => 'Approved',
            ],
            'R5.2' => [
                'response' => '000',
                'message' => 'Approved',
            ],
            'R5.3' => [
                'response' => '484',
                'message' => 'Insufficient data to update subscription',
            ],
            'R5.4' => [
                'response' => '485',
                'message' => 'Invalid billing date',
            ],
            'R6.2' => [
                'response' => 475,
                'message' => 'Invalid subscription id',
            ],
            'R7.2' => [
                'response' => 476,
                'message' => 'Add-on code already exists',
            ],
            'R7.3' => [
                'response' => 486,
                'message' => 'Discount code already exists',
            ],
            'R7.4' => [
                'response' => 478,
                'message' => 'No matching add-on code for the subscription',
            ],
            'R7.5' => [
                'response' => 480,
                'message' => 'No matching discount code for the subscription',
            ],
        ];
        if (!isset($data[$orderId])) {
            new \Magento\Framework\Exception\LocalizedException(__('Test response not found'));
        }

        return $data[$orderId];
    }

    /**
     * @param array $data
     * @return string
     */
    private function createBillToAddressNode(array $data)
    {
        if (isset($data['billToAddress'])) {
            return
                '<billToAddress>' .
                    '<name>' . $data['billToAddress']['name'] . '</name>' .
                    '<addressLine1>' . $data['billToAddress']['addressLine1'] . '</addressLine1>' .
                    '<city>' . $data['billToAddress']['city'] . '</city>' .
                    '<state>' . $data['billToAddress']['state'] . '</state>' .
                    '<zip>' . $data['billToAddress']['zip'] . '</zip>' .
                    '<country>' . $data['billToAddress']['country'] . '</country>' .
                    '<email>' . $data['billToAddress']['email'] . '</email>' .
                    '<phone>' . $data['billToAddress']['phone'] . '</phone>' .
                '</billToAddress>';
        }
        return '';
    }

    /**
     * @param array $data
     * @return string
     */
    private function createCardNode(array $data)
    {
        if (isset($data['card'])) {
            return
                '<card>' .
                    '<type>' . $data['card']['type'] . '</type>' .
                    '<number>' . $data['card']['number'] . '</number>' .
                    '<expDate>' . $data['card']['expDate'] . '</expDate>' .
                '</card>';
        }
        return '';
    }

    /**
     * @param array $data
     * @return string
     */
    private function createAddOnNode(array $data)
    {
        if (isset($data['createAddOn'])) {
            return
                '<createAddOn>' .
                    '<addOnCode>' . $data['createAddOn']['addOnCode'] . '</addOnCode>' .
                    '<name>' . $data['createAddOn']['name'] . '</name>' .
                    '<amount>' . $data['createAddOn']['amount'] . '</amount>' .
                    '<startDate>' . $data['createAddOn']['startDate'] . '</startDate>' .
                    '<endDate>' . $data['createAddOn']['endDate'] . '</endDate>' .
                '</createAddOn>';
        }
        return '';
    }

    /**
     * @param array $data
     * @return string
     */
    private function createDiscountNode(array $data)
    {
        if (isset($data['createDiscount'])) {
            return
                '<createDiscount>' .
                    '<discountCode>' . $data['createDiscount']['discountCode'] . '</discountCode>' .
                    '<name>' . $data['createDiscount']['name'] . '</name>' .
                    '<amount>' . $data['createDiscount']['amount'] . '</amount>' .
                    '<startDate>' . $data['createDiscount']['startDate'] . '</startDate>' .
                    '<endDate>' . $data['createDiscount']['endDate'] . '</endDate>' .
                '</createDiscount>';
        }
        return '';
    }

    /**
     * @param array $data
     * @return string
     */
    private function createUpdateAddOnNode(array $data)
    {
        if (isset($data['updateAddOn'])) {
            return
                '<updateAddOn>' .
                    '<addOnCode>' . $data['updateAddOn']['addOnCode'] . '</addOnCode>' .
                    '<name>' . $data['updateAddOn']['name'] . '</name>' .
                    '<amount>' . $data['updateAddOn']['amount'] . '</amount>' .
                '</updateAddOn>';
        }
        return '';
    }
    /**
     * @param array $data
     * @return string
     */
    private function createUpdateDiscountNode(array $data)
    {
        if (isset($data['updateDiscount'])) {
            return
                '<updateDiscount>' .
                    '<discountCode>' . $data['updateDiscount']['discountCode'] . '</discountCode>' .
                    '<name>' . $data['updateDiscount']['name'] . '</name>' .
                    '<amount>' . $data['updateDiscount']['amount'] . '</amount>' .
                    '<startDate>' . $data['updateDiscount']['startDate'] . '</startDate>' .
                    '<endDate>' . $data['updateDiscount']['endDate'] . '</endDate>' .
                '</updateDiscount>';
        }
        return '';
    }
}
