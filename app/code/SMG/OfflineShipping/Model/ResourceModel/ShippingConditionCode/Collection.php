<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 2:51 PM
 */

namespace SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\SMG\OfflineShipping\Model\ShippingConditionCode::class,
            \SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode::class);
    }
}