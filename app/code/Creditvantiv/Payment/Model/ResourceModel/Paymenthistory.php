<?php
namespace Creditvantiv\Payment\Model\ResourceModel;
/**
* Payment Resource Model
*
* @method \Creditvantiv\Payment\Model\Resource\Page _getResource()
* @method \Creditvantiv\Payment\Model\Resource\Page getResource()
*/
class Paymenthistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	/**
	* Initialize resource model
	*
	* @return void
	*/
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}

	protected function _construct(){
		$this->_init('sales_order_credit_batch_history', 'response_id');
	}

}