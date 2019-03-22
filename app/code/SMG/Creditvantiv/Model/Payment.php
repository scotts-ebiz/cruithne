<?php
namespace SMG\Creditvantiv\Model;
use Magento\Framework\Model\AbstractModel;
/**
* Payment Model
*
* @method \Creditvantiv\Payment\Model\Resource\Page _getResource()
* @method \Creditvantiv\Payment\Model\Resource\Page getResource()
*/
class Payment extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'sales_order_credit_batch';

	protected $_cacheTag = 'sales_order_credit_batch';

	protected $_eventPrefix = 'sales_order_credit_batch';

	/**
	* Initialize resource model
	*
	* @return void
	*/
	protected function _construct(){

		$this->_init('SMG\Creditvantiv\Model\ResourceModel\Payment');
		
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}

}