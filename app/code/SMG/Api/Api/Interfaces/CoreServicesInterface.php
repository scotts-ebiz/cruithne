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



}
