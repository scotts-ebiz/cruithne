<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardActivateVirtualRenderer;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardActivateRenderer;

/**
 * GiftCard activate builder.
 */
class GiftCardActivateBuilder extends AbstractGiftcardRequestBuilder
{
    /**
     * Activate renderer.
     *
     * @var GiftCardActivateVirtualRenderer
     */
    private $giftCardActivateVirtualRenderer = null;

    /**
     * VirtualGiftCard renderer.
     *
     * @var GiftCardActivateRenderer
     */
    private $giftCardActivateRenderer = null;

    /**
     * @param SubjectReader $reader
     * @param VantivGiftcardConfig $config
     * @param GiftCardActivateVirtualRenderer $giftCardActivateVirtualRenderer
     * @param GiftCardActivateRenderer $giftCardActivateRenderer
     */
    public function __construct(
        SubjectReader $reader,
        VantivGiftcardConfig $config,
        GiftCardActivateRenderer $giftCardActivateRenderer,
        GiftCardActivateVirtualRenderer $giftCardActivateVirtualRenderer
    ) {
        parent::__construct($reader, $config);

        $this->giftCardActivateRenderer = $giftCardActivateRenderer;
        $this->giftCardActivateVirtualRenderer = $giftCardActivateVirtualRenderer;
    }

    /**
     * Build <activate> XML node.
     *
     * <activate reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <VIRTUAL_GIFT_CARD_NODE/> | <CARD_NODE/>
     * </activate>
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
            'amount' => $this->getReader()->readAmount($subject) * 100,
            'orderSource' => 'ecommerce',
        ];
        $data += $this->getAuthenticationData($subject);
        if (isset($subject['type']) && $subject['type'] == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL) {
            $data['accountNumberLength'] = $subject['accountNumberLength'];
            $data['giftCardBin'] = $subject['giftCardBin'];
            $xml = $this->giftCardActivateVirtualRenderer->render($data);
        } else {
            $data['type'] = self::GIFT_CARD_TYPE;
            $data['number'] = array_key_exists('giftcard_code', $subject) ? $subject['giftcard_code'] : '';
            $xml = $this->giftCardActivateRenderer->render($data);
        }

        return $xml;
    }
}
