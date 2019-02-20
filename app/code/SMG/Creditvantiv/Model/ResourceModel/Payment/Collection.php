<?php
namespace SMG\Creditvantiv\Model\ResourceModel\Payment;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'sales_order_credit_batch_collection';
	protected $_eventObject = 'batch_collection';

	/**
	* Resource initialization
	*
	* @return void
	*/
	protected function _construct(){
		$this->_init('SMG\Creditvantiv\Model\Payment', 'SMG\Creditvantiv\Model\ResourceModel\Payment');
	}
}