<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Shipment;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Track Import
 */
class Track extends AbstractAdapter
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
     * Shipment Id Column Name
     *
     */
    const COLUMN_SHIPMENT_ID = 'parent_id';  
	
    /**
     * Order Id Column Name
     *
     */
    const COLUMN_ORDER_ID = 'order_id'; 
    
    /**
     * Track Number Column Name
     *
     */
    const COLUMN_TRACK_NUMBER = 'track_number';     

    /**
     * Error Codes
     */       
	const ERROR_ENTITY_ID_IS_EMPTY = 'shipmentTrackIdIsEmpty';
	const ERROR_SHIPMENT_ID_IS_EMPTY = 'shipmentTrackParentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateShipmentTrackId';	
	const ERROR_ORDER_ID_IS_EMPTY = 'shipmentTrackOrderIdIsEmpty';
	const ERROR_TRACK_NUMBER_IS_EMPTY = 'shipmentTrackNumberIsEmpty';	
	
    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Shipment Track entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Shipment Track entity_id is empty',
        self::ERROR_SHIPMENT_ID_IS_EMPTY => 'Shipment Track parent_id is empty',
		self::ERROR_ORDER_ID_IS_EMPTY => 'Shipment Track order_id is empty',
		self::ERROR_TRACK_NUMBER_IS_EMPTY => 'Shipment Track track_number is empty',		
    ];

    /**
     * Order Shipment Track Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_shipment_track';  
    
    /**
     * Retrieve The Prepared Data
     *
     * @param array $rowData
     * @return array|bool
     */
    public function prepareRowData(array $rowData)
    {
		parent::prepareRowData($rowData);
		$rowData = $this->_extractField($rowData, 'shipment_track');
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
			':order_id' => $this->_getOrderId($rowData),
			':parent_id' => $this->_getShipmentId($rowData),
			':track_number' => $rowData['track_number']
		];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
			->where('parent_id = :parent_id')
			->where('order_id = :order_id')
			->where('track_number = :track_number');

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
        
        list($createdAt, $updatedAt) = $this->_prepareDateTime($rowData);

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

		$entityRow = [  
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,		
            'entity_id' => $entityId,
			'parent_id' => $this->_getShipmentId($rowData),
			'order_id' => $this->_getOrderId($rowData)			
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
			if (empty($rowData[self::COLUMN_SHIPMENT_ID])) {
				$this->addRowError(self::ERROR_SHIPMENT_ID_IS_EMPTY, $rowNumber);
			} 
			
			if (empty($rowData[self::COLUMN_ORDER_ID])) {
				$this->addRowError(self::ERROR_ORDER_ID_IS_EMPTY, $rowNumber);
			} 
			
			if (empty($rowData[self::COLUMN_TRACK_NUMBER])) {
				$this->addRowError(self::ERROR_TRACK_NUMBER_IS_EMPTY, $rowNumber);
			} 			
        }
    }   
}
