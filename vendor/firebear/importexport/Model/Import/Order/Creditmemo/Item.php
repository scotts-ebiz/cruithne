<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Creditmemo;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Creditmemo Item Import
 */
class Item extends AbstractAdapter
{
    /**
     * Entity Type Code
     *
     */
    const ENTITY_TYPE_CODE = 'order'; 

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Creditmemo Id Column Name
     *
     */
    const COLUMN_CREDITMEMO_ID = 'parent_id';  
	
    /**
     * Order Item Id Column Name
     *
     */
    const COLUMN_ORDER_ITEM_ID = 'order_item_id'; 
    
    /**
     * Error Codes
     */       
	const ERROR_ENTITY_ID_IS_EMPTY = 'creditmemoItemIdIsEmpty';
	const ERROR_CREDITMEMO_ID_IS_EMPTY = 'creditmemoItemParentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateCreditmemoItemId';	
	const ERROR_ORDER_ITEM_ID_IS_EMPTY = 'creditmemoItemOrderItemIdIsEmpty';
	
    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Creditmemo Item entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Creditmemo Item entity_id is empty',
        self::ERROR_CREDITMEMO_ID_IS_EMPTY => 'Creditmemo Item parent_id is empty',
		self::ERROR_ORDER_ITEM_ID_IS_EMPTY => 'Creditmemo Item order_item_id is empty',
    ];
    
    /**
     * Order Creditmemo Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_creditmemo_item';  

   /**
     * Retrieve The Prepared Data
     *
     * @param array $rowData
     * @return array|bool
     */
    public function prepareRowData(array $rowData)
    {
		parent::prepareRowData($rowData);
		$rowData = $this->_extractField($rowData, 'creditmemo_item');
		return (count($rowData) && !$this->isEmptyRow($rowData)) 
			? $rowData 
			: false;
    }
	
    /**
     * Retrieve Entity Id If Entity Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistEntityId(array $rowData)
    {
        $bind = [
			':order_item_id' => $this->_getOrderItemId($rowData),
			':parent_id' => $this->_getCreditmemoId($rowData)
		];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
			->where('parent_id = :parent_id')
			->where('order_item_id = :order_item_id');

        return $this->_connection->fetchOne($select, $bind);
    } 
	
    /**
     * Prepare Data For Update
     *
     * @param array $rowData
     * @return array
     */
    protected function _prepareDataForUpdate(array $rowData)
    {
        $toCreate = [];
        $toUpdate = [];

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }
        
		$entityRow = [           
            'entity_id' => $entityId,
			'parent_id' => $this->_getCreditmemoId($rowData),
			'order_item_id' => $this->_getOrderItemId($rowData)
        ];
		/* prepare data */
		$entityRow = $this->_prepareEntityRow($entityRow, $rowData);
        if ($newEntity) {
            $toCreate[] = $entityRow;
        } else {
            $toUpdate[] = $entityRow;
        }
        return [
            self::ENTITIES_TO_CREATE_KEY => $toCreate,
            self::ENTITIES_TO_UPDATE_KEY => $toUpdate
        ];		
    }

    /**
     * Validate Row Data For Add/Update Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if ($this->_checkEntityIdKey($rowData, $rowNumber)) {
			if (empty($rowData[self::COLUMN_CREDITMEMO_ID])) {
				$this->addRowError(self::ERROR_CREDITMEMO_ID_IS_EMPTY, $rowNumber);
			} 
			
			if (empty($rowData[self::COLUMN_ORDER_ITEM_ID])) {
				$this->addRowError(self::ERROR_ORDER_ITEM_ID_IS_EMPTY, $rowNumber);
			} 			
        }
    }
}
