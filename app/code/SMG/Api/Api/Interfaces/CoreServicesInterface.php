<?php

namespace SMG\Api\Api\Interfaces;

interface CoreServicesInterface
{
    /**
     * This function will create an order and return it in a JSON format.
     *
     * @return string
     */
    public function createOrder();

    /**
     * This function will get an order and return it in a JSON format.
     *
     * @return string
     */
    public function getOrder();

    /**
     * This function will update the subscription order status.
     *
     * @return string
     */
    public function updateOrderSubscriptionStatus();

    /**
     * Gets an array of products by their sku(s).
     *
     * @return string
     */
    public function getProducts();

    /**
     * Creates a shipment given order/shipment details.
     *
     * @return string
     */
    public function createShipment();

    /**
     * This function will update the billing address for an order.
     *
     * @return string
     */
    public function UpdateBillingAddress();
	
	 /**
     * This function will update the cusomer email address for an orders.
     *
     * @return string
     */
    public function updateEmailAddress();



}
