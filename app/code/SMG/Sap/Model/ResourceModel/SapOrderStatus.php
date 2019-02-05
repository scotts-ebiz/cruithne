<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 2:42 PM
 */

namespace SMG\Sap\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SapOrderStatus extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sales_order_status_sap', 'status');
    }
}