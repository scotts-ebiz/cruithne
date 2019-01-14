<?php
namespace Creditvantiv\Payment\Model\ResourceModel\Paymenthistory;
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
		$this->_init('Creditvantiv\Payment\Model\Paymenthistory', 'Creditvantiv\Payment\Model\ResourceModel\Paymenthistory');
	}
}