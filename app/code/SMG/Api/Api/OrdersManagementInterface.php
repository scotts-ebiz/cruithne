<?php

namespace SMG\Api\Api;

interface OrdersManagementInterface
{
    /**
     * This function will get the orders in a JSON format.
     *
     * @return string
     */
    public function getOrders();

    /**
     * This function will get the credit memo orders in a JSON format.
     *
     * @return string
     */
    public function getCreditMemoOrders();

    /**
     * This function will get the Lawn Subscription orders in a JSON format.
     *
     * @return string
     */
    public function getLawnSubscriptionOrders();

    /**
     * This function will get the main orders in a JSON format.  This replaces the getOrders
     * as it is all M2 orders including seasonal subscriptions except the credit memos and
     * lawn subscription orders.
     *
     * @return string
     */
    public function getMainOrders();

    /**
     * This function will get the orders in a JSON format for order audit.
     *
     * @return SMG\Api\Model\OrdersManagement[]
     */
    public function getOrdersForAudit();

    /**
     * This function will get the sap batch in a JSON format for order audit.
     *
     * @return SMG\Api\Model\OrdersManagement[]
     */
    public function getSapBatchForAudit();
}