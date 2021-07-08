<?php
/**
 * User: cnixon
 * Date: 6/17/21
 * Time: 9:08 AM
 */

namespace SMG\Api\Api\Interfaces;

interface FraudManagementInterface
{
    /**
     * This function will run fraud checks.
     *
     * @return boolean
     */
    public function runFraudCheck();

}
