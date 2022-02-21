<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardDeactivateRenderer;

/**
 * GiftCard deactivate builder.
 */
class GiftCardDeactivateBuilder extends AbstractGiftcardRequestBuilder
{
    /**
     * GiftCard node builder.
     *
     * @var GiftCardDeactivateRenderer
     */
    private $giftCardDeactivateRenderer = null;

    /**
     * @param \Vantiv\Payment\Gateway\Common\SubjectReader $reader
     * @param \Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig $config
     * @param \Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardDeactivateRenderer $giftCardDeactivateRenderer
     */
    public function __construct(
        SubjectReader $reader,
        VantivGiftcardConfig $config,
        GiftCardDeactivateRenderer $giftCardDeactivateRenderer
    ) {
        parent::__construct($reader, $config);

        $this->giftCardDeactivateRenderer = $giftCardDeactivateRenderer;
    }

    /**
     * Build <deactivate> XML node.
     *
     * <deactivate reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <orderSource>ecommerce</orderSource>
     *     <card>
     *         <type>GC</type>
     *         <number>GIFT_CARD_ACCOUNT_CODE</number>
     *     </card>
     * </deactivate>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        $data = [
            'reportGroup' => $this->getConfig()->getValue('report_group'),
            'customerId' => $this->getReader()->readOrderAdapter($subject)->getCustomerId(),
            'id' => $this->getId(),
            'orderId' => $this->getReader()->readOrderAdapter($subject)->getOrderIncrementId(),
            'orderSource' => 'ecommerce',
            'number' => $subject['giftcard_code'],
            'type' => self::GIFT_CARD_TYPE,
        ];
        $data += $this->getAuthenticationData($subject);

        return $this->giftCardDeactivateRenderer->render($data);
    }
}
