<?php
namespace SMG\Creditvantiv\Model\ResourceModel\Paymenthistory;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'response_id';
	protected $_eventPrefix = 'sales_order_credit_batch_history_collection';
	protected $_eventObject = 'history_collection';

	/**
	* Resource initialization
	*
	* @return void
	*/
	protected function _construct(){
		$this->_init('SMG\Creditvantiv\Model\Paymenthistory', 'SMG\Creditvantiv\Model\ResourceModel\Paymenthistory');
	}
}