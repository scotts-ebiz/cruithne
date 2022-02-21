<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Plugin\GiftCardAccount\Observer;

/**
 * Class CreateGiftCard
 */
class CreateGiftCard
{
    /**
     * @var int
     */
    const IS_REDEEMABLE = 0;

    /**
     * Set Gift Card Account as not redeemable
     *
     * @param \Magento\GiftCardAccount\Observer\CreateGiftCard $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        \Magento\GiftCardAccount\Observer\CreateGiftCard $subject,
        \Magento\Framework\Event\Observer $observer
    ) {
        $data = $observer->getEvent()->getRequest();
        $data->setIsRedeemable(self::IS_REDEEMABLE);

        return [$observer];
    }
}
