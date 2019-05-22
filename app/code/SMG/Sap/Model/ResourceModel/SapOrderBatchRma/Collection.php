<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */

namespace SMG\Sap\Model\ResourceModel\SapOrderBatchRma;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\SMG\Sap\Model\SapOrderBatchRma::class,
            \SMG\Sap\Model\ResourceModel\SapOrderBatchRma::class);
    }
}