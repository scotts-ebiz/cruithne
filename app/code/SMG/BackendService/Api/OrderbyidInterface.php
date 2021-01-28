<?php


namespace SMG\BackendService\Api;

interface OrderbyidInterface
{

    /**
     * GET for orderReturn api
     * @param string $orderIncrementid
     * @param string $email
     * @return string
     */
    public function getOrderById($orderIncrementid, $email);
}

