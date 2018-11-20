<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Builder;

use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\Common\Renderer\GiftCard\GiftCardSaleRenderer;

/**
 * GiftCard sale builder.
 */
class GiftCardSaleBuilder extends AbstractGiftcardRequestBuilder
{
    /**
     * GiftCardSaleRenderer renderer
     *
     * @var GiftCardSaleRenderer
     */
    private $giftCardSaleRenderer = null;

    /**
     * @param SubjectReader $reader
     * @param VantivGiftcardConfig $config
     * @param GiftCardSaleRenderer $giftCardSaleRenderer
     */
    public function __construct(
        SubjectReader $reader,
        VantivGiftcardConfig $config,
        GiftCardSaleRenderer $giftCardSaleRenderer
    ) {
        parent::__construct($reader, $config);

        $this->giftCardSaleRenderer = $giftCardSaleRenderer;
    }

    /**
     * Build <sale> XML node.
     *
     * <sale reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <CARD_NODE/>
     * </sale>
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

        $data['type'] = self::GIFT_CARD_TYPE;
        $data['number'] = $subject['number'];
        $xml = $this->giftCardSaleRenderer->render($data);

        return $xml;
    }
}
