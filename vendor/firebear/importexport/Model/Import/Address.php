<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\CustomerImportExport\Model\Import\Address as MagentoAddress;
use Magento\Framework\App\ObjectManager;
use Magento\ImportExport\Model\Import\AbstractEntity;

class Address extends MagentoAddress
{
    use \Firebear\ImportExport\Traits\General;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $output;

    protected $duplicateFields = [];
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    
    /**
     * Customers Ids
     *
     * @var array[]
     */
    protected $_customerIds = [];

    /**
     * @return MagentoAddress|void
     */
    protected function _initAttributes()
    {
        $objectManager = ObjectManager::getInstance();
        $this->output = $objectManager
            ->get('Symfony\Component\Console\Output\ConsoleOutput');

        if ($this->_dataSourceModel instanceof \Magento\ImportExport\Model\ResourceModel\Import\Data) {
            $this->_dataSourceModel = $objectManager
                ->create('Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite\Data', [
                    'arguments' => [
                        'entity_type' => 'address'
                    ],
                ]);
        }

        if (!$this->_logger) {
            $this->_logger = $objectManager->get('Psr\Log\LoggerInterface');
        }

        parent::_initAttributes();
    }

    
    /**
     * @return array
     */
    public function getAllFields()
    {
        $options = array_merge($this->getValidColumnNames(), $this->_specialAttributes);
        $options = array_merge($options, $this->_permanentAttributes);

        return array_unique($options);
    }

    public function customChangeData($rowData)
    {
        // Add _entity_id if field increment_id exists
        $columnIncrementId = 'increment_id';
        if(!empty($rowData[$columnIncrementId])) {
            $select = $this->_connection->select()
                ->from($this->_entityTable, 'entity_id')
                ->where($columnIncrementId.' = ?', $rowData[$columnIncrementId]);

            $entityId = $this->_connection->fetchOne($select);
            if($entityId) {
                $rowData[static::COLUMN_ADDRESS_ID] = $entityId;
            }
        }

        return $rowData;
    }

    /**
     * @return bool
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $newRows = [];
            $updateRows = [];
            $attributes = [];
            $defaults = [];
            $this->prepareCustomerData($bunch);

            $deleteRowIds = [];
            $this->prepareCustomerData($bunch);
            foreach ($bunch as $rowNumber => $rowData) {
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $email = $rowData['_email'];
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                if ($this->_isOptionalAddressEmpty($rowData) || !$this->validateRow($rowData, $rowNumber)) {
                    $this->addLogWriteln(__('address with email: %1 is not valided', $email), $this->output, 'info');
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                if (\Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior($rowData)) {
                    $updateResult = $this->_prepareDataForUpdate($rowData);
                    if ($updateResult['entity_row_new']) {
                        $newRows[] = $updateResult['entity_row_new'];
                    }
                    if ($updateResult['entity_row_update']) {
                        $updateRows[] = $updateResult['entity_row_update'];
                    }
                    $attributes = $this->_mergeEntityAttributes($updateResult['attributes'], $attributes);
                    $defaults = $this->_mergeEntityAttributes($updateResult['defaults'], $defaults);
                } elseif ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
                    $deleteRowIds[] = $rowData[self::COLUMN_ADDRESS_ID];
                }
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(
                    __('address with email: %1 .... %2s', $email, $totalTime),
                    $this->output,
                    'info'
                );
            }
            $this->updateItemsCounterStats(
                $newRows,
                $updateRows,
                $deleteRowIds
            );
            $this->_saveAddressEntities(
                $newRows,
                $updateRows
            )->_saveAddressAttributes(
                $attributes
            )->_saveCustomerDefaults(
                $defaults
            );

            $this->_deleteAddressEntities($deleteRowIds);
        }
        return true;
    }
    
    /**
     * Set customer id
     *
     * @param string $email
     * @param string $websiteCode
     * @param integer $customerId
     * @return $this
     */
    public function setCustomerId($email, $websiteCode, $customerId)
    {
        $email = strtolower(trim($email));
        $this->_customerIds[$email][$websiteCode] = $customerId;
        return $this;
    }
    
    /**
     * Get customer id if customer is present in database
     *
     * @param string $email
     * @param string $websiteCode
     * @return bool|int
     */
    protected function _getCustomerId($email, $websiteCode)
    {
        $email = strtolower(trim($email));
        if (isset($this->_websiteCodeToId[$websiteCode])) {
            $websiteId = $this->_websiteCodeToId[$websiteCode];
            if (isset($this->_customerIds[$email][$websiteId])) {
                return $this->_customerIds[$email][$websiteId];
            }
        }
        return parent::_getCustomerId($email, $websiteCode);
    }
    
    public function _mergeEntityAttributes(array $newAttributes, array $attributes)
    {
        return parent::_mergeEntityAttributes($newAttributes, $attributes); // TODO: Change the autogenerated stub
    }

    public function _prepareDataForUpdate(array $rowData):array
    {
        $updateData = parent::_prepareDataForUpdate($rowData);
        if ($updateData['entity_row_new'] && count($updateData['entity_row_new'])) {
            $updateData['entity_row_new']['entity_id'] = $rowData['_entity_id'];

            $defaults = [];
            foreach (self::getDefaultAddressAttributeMapping() as $columnName => $attributeCode) {
                if (!empty($rowData[$columnName]) && $rowData[self::COLUMN_ADDRESS_ID]) {
                    $email = strtolower($rowData[self::COLUMN_EMAIL]);
                    $customerId = $this->_getCustomerId($email, $rowData[self::COLUMN_WEBSITE]);
                    $table = $this->_getCustomerEntity()->getResource()->getTable('customer_entity');
                    $defaults[$table][$customerId][$attributeCode] = $rowData[self::COLUMN_ADDRESS_ID];
                }
            }
            if (!empty($defaults)) {
                $updateData['defaults'] = $defaults;
            }
        }
        return $updateData;
    }

    public function _saveAddressEntities(array $addRows, array $updateRows)
    {
        return parent::_saveAddressEntities($addRows, $updateRows); // TODO: Change the autogenerated stub
    }

    public function _saveAddressAttributes(array $attributesData)
    {
        return parent::_saveAddressAttributes($attributesData); // TODO: Change the autogenerated stub
    }

    public function _saveCustomerDefaults(array $defaults)
    {
        return parent::_saveCustomerDefaults($defaults); // TODO: Change the autogenerated stub
    }

    public function _deleteAddressEntities(array $entityRowIds)
    {
        return parent::_deleteAddressEntities($entityRowIds); // TODO: Change the autogenerated stub
    }

    public function _isOptionalAddressEmpty(array $rowData)
    {
        return parent::_isOptionalAddressEmpty($rowData); // TODO: Change the autogenerated stub
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
        $this->prepareCustomerData($source);
        $bunchRows = [];
        $startNewBunch = false;

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $masterAttributeCode = $this->getMasterAttributeCode();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }
        while ($source->valid() || count($bunchRows) || isset($entityGroup)) {
            if ($startNewBunch || !$source->valid()) {
                /* If the end approached add last validated entity group to the bunch */
                if (!$source->valid() && isset($entityGroup)) {
                    foreach ($entityGroup as $key => $value) {
                        $bunchRows[$key] = $value;
                    }
                    unset($entityGroup);
                }
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );

                $bunchRows = [];
                $startNewBunch = false;
            }
            if ($source->valid()) {
                $valid = true;
                try {
                    $rowData = $source->current();
                    foreach ($rowData as $attrName => $element) {
                        if (!mb_check_encoding($element, 'UTF-8')) {
                            $valid = false;
                            $this->addRowError(
                                AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                                $this->_processedRowsCount,
                                $attrName
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $valid = false;
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                }
                if (!$valid) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->customBunchesData($rowData);
                if (isset($rowData[$masterAttributeCode]) && trim($rowData[$masterAttributeCode])) {
                    /* Add entity group that passed validation to bunch */
                    if (isset($entityGroup)) {
                        foreach ($entityGroup as $key => $value) {
                            $bunchRows[$key] = $value;
                        }
                        $productDataSize = strlen(\Zend\Serializer\Serializer::serialize($bunchRows));

                        /* Check if the new bunch should be started */
                        $isBunchSizeExceeded = ($this->_bunchSize > 0 && count($bunchRows) >= $this->_bunchSize);
                        $startNewBunch = $productDataSize >= $this->_maxDataSize || $isBunchSizeExceeded;
                    }

                    /* And start a new one */
                    $entityGroup = [];
                }

                if (isset($entityGroup) && $this->validateRow($rowData, $source->key())) {
                    /* Add row to entity group */
                    $entityGroup[$source->key()] = $this->_prepareRowForDb($rowData);
                } elseif (isset($entityGroup)) {
                    /* In case validation of one line of the group fails kill the entire group */
                    unset($entityGroup);
                }

                $this->_processedRowsCount++;
                $source->next();
            }
        }
        return $this;
    }
}
