<?php
/**
 * User: cnixon
 * Date: 6/17/21
 * Time: 9:08 AM
 */

namespace SMG\Api\Api;

use SMG\Api\Api\Interfaces\FraudManagementInterface;
use SMG\Api\Helper\FraudHelper;


class FraudManagement implements FraudManagementInterface
{

    /**
     * @var FraudHelper
     */
    protected $_fraudHelper;

    /**
     * FraudManagement constructor.
     *
     * @param FraudHelper $fraudHelper
     */
    public function __construct(
        FraudHelper $fraudHelper
    )
    {
        $this->_fraudHelper = $fraudHelper;
    }

    /**
     * Run fraud check.
     *
     * @return string
     */
    public function runFraudCheck()
    {
        return $this->_fraudHelper->fraudCheck();
    }

}
