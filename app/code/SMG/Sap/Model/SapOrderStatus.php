<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 2:37 PM
 */

namespace SMG\Sap\Model;

use Magento\Framework\Model\AbstractModel;

class SapOrderStatus extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\ResourceModel\SapOrderStatus::class);
    }
}