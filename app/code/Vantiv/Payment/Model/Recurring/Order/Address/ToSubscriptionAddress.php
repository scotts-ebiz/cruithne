<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Order\Address;

class ToSubscriptionAddress
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\AddressFactory
     */
    private $addressFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Vantiv\Payment\Model\Recurring\Subscription\AddressFactory $addressFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Vantiv\Payment\Model\Recurring\Subscription\AddressFactory $addressFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->addressFactory = $addressFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * Convert order/order item data to subscription
     *
     * @param \Magento\Sales\Model\Order\Address $orderAddress
     * @param array $data
     * @return \Vantiv\Payment\Model\Recurring\Subscription\Address
     */
    public function convert(\Magento\Sales\Model\Order\Address $orderAddress, $data = [])
    {
        $address = $this->addressFactory->create();

        $addressData = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_order_address',
            'to_vantiv_subscription_address',
            $orderAddress
        );

        $address->addData(array_merge($addressData, $data));

        $this->eventManager->dispatch(
            'sales_convert_order_address_to_vantiv_subscription_address',
            ['order_address' => $orderAddress, 'subscription_address' => $address]
        );

        return $address;
    }
}
