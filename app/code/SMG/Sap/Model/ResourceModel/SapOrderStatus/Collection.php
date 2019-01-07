<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 2:51 PM
 */

namespace SMG\Sap\Model\ResourceModel\SapOrderStatus;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\SapOrderStatus::class,
            \SMG\Sap\Model\ResourceModel\SapOrderStatus::class);
    }
}