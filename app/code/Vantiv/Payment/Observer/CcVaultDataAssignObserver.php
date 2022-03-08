<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class DataAssignObserver
 */
class CcVaultDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var Json
     */
    private $jsonDecoder;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param Json $jsonDecoder
     */
    public function __construct(PaymentTokenManagementInterface $paymentTokenManagement, Json $jsonDecoder)
    {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->jsonDecoder = $jsonDecoder;
    }

    /**
     * Assign credit card data to payment info.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $this->readPaymentModelArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (isset($additionalData[PaymentTokenInterface::PUBLIC_HASH])) {
            $token = $this->paymentTokenManagement->getByPublicHash(
                $additionalData[PaymentTokenInterface::PUBLIC_HASH],
                $payment->getAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID)
            );
            $tokenDetails = $this->jsonDecoder->unserialize($token->getTokenDetails());
            if (isset($tokenDetails['ccType'])) {
                $payment->setCcType($tokenDetails['ccType']);
            }
        }
    }
}
