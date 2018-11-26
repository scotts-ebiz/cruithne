<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Plugin\GiftCardAccount\Model;

use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

/**
 * Class Pool
 */
class Pool
{
    /**
     * Vantiv Gift Card settings
     *
     * @var VantivGiftcardConfig
     */
    private $giftcardConfig;

    /**
     * Constructor.
     *
     * @param VantivGiftcardConfig $giftcardConfig
     */
    public function __construct(VantivGiftcardConfig $giftcardConfig)
    {
        $this->giftcardConfig = $giftcardConfig;
    }

    /**
     * Disable Gift Card codes generaration cron job
     * in order to avoid the deletion of imported Vantiv Gift Card codes
     *
     * @param \Magento\GiftCardAccount\Model\Pool $subject
     * @param \Closure $proceed
     * @return \Magento\GiftCardAccount\Model\Pool
     */
    public function aroundApplyCodesGeneration(
        \Magento\GiftCardAccount\Model\Pool $subject,
        \Closure $proceed
    ) {
        return $this->giftcardConfig->getValue('active') ? $subject : $proceed();
    }
}
