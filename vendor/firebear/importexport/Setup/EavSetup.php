<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Setup;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Eav\Setup\EavSetup as OriginEavSetup;
use Magento\Setup\Exception;

/**
 * Eav Setup
 */
class EavSetup extends OriginEavSetup
{
    /**
     * Attribute mapper
     *
     * @var PropertyMapperInterface
     */
    private $attributeMapper;

    /**
     * Setup model
     *
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * General Attribute Group Name
     *
     * @var string
     */
    private $_generalGroupName = 'General';

    /**
     * Init
     *
     * @param ModuleDataSetupInterface $setup
     * @param Context $context
     * @param CacheInterface $cache
     * @param CollectionFactory $attrGroupCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Context $context,
        CacheInterface $cache,
        CollectionFactory $attrGroupCollectionFactory
    ) {
        $this->attributeMapper = $context->getAttributeMapper();
        $this->setup = $setup;

        parent::__construct(
            $setup,
            $context,
            $cache,
            $attrGroupCollectionFactory
        );
    }

    /**
     * Add attribute to an entity type
     * If attribute is system will add to all existing attribute sets
     *
     * @param string|integer $entityTypeId
     * @param string $attributeCode
     * @param array $attr
     *
     * @return $this
     */
    public function addAttribute($entityTypeId, $attributeCode, array $attr)
    {
        $entityTypeId = $this->getEntityTypeId($entityTypeId);
        $setId = $attr['attribute_set_id'];
        unset($attr['attribute_set_id']);

        if (count($attr) == 3 && isset($attr['option']) && isset($attr['store_id']) && isset($attr['attribute_id'])) {
            $option = $attr['option'];
            $option['attribute_id'] = $this->getAttributeId($entityTypeId, $attributeCode);
            $this->addAttributeOption($option);
            return;
        }

        $data = array_replace(
            ['entity_type_id' => $entityTypeId, 'attribute_code' => $attributeCode],
            $attr
        );

        $sortOrder = $attr['sort_order'] ?? null;
        $attributeId = $attr['attribute_id'] ?? null;
        if ($attributeId) {
            $this->updateAttribute($entityTypeId, $attributeId, $data, null, $sortOrder);
        } else {
            $data = array_merge($this->attributeMapper->map($attr, $entityTypeId), $data);
            $this->_insertAttribute($data);
        }

        if (!empty($attr['group']) || empty($attr['user_defined']) || $setId) {
            if (!empty($attr['group']) && $setId) {
                $this->addAttributeGroup($entityTypeId, $setId, $attr['group']);
                $this->addAttributeToSet(
                    $entityTypeId,
                    $setId,
                    $attr['group'],
                    $attributeCode,
                    $sortOrder
                );
            } elseif ($setId) {
                $this->addAttributeToSet(
                    $entityTypeId,
                    $setId,
                    $this->_generalGroupName,
                    $attributeCode,
                    $sortOrder
                );
            }
        }

        if (isset($attr['option']) && is_array($attr['option'])) {
            $option = $attr['option'];
            $option['attribute_id'] = $this->getAttributeId($entityTypeId, $attributeCode);
            $this->addAttributeOption($option);
        }

        if (isset($data['store_id'], $data['store_labels'])) {
            $this->updateStoreLabel($data['store_labels'], $attributeId);
        }

        return $this;
    }

    /**
     * @param $storeLabels
     * @param null $attributeId
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function updateStoreLabel($storeLabels, $attributeId = null)
    {
        if (\is_array($storeLabels)) {
            $connection = $this->setup->getConnection();
            foreach ($storeLabels as $storeId => $label) {
                if ($storeId === 0 || $label === '') {
                    continue;
                }
                $bind = ['attribute_id' => $attributeId, 'store_id' => $storeId, 'value' => $label];
                $connection->insertOnDuplicate($this->setup->getTable('eav_attribute_label'), $bind);
            }
        }
        return $this;
    }

    /**
     * Add Attribute Option
     *
     * @param array $optionData
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addAttributeOption($optionData)
    {
        $optionTable = $this->setup->getTable('eav_attribute_option');
        $optionValueTable = $this->setup->getTable('eav_attribute_option_value');

        if (isset($optionData['value'])) {
            foreach ($optionData['value'] as $optionId => $values) {
                $intOptionId = (int)$optionId;
                if (!empty($optionData['delete'][$optionId])) {
                    if ($intOptionId) {
                        $condition = ['option_id =?' => $intOptionId];
                        $this->setup->getConnection()->delete($optionTable, $condition);
                    }
                    continue;
                }

                if (!$intOptionId) {
                    $data = [
                        'attribute_id' => $optionData['attribute_id'],
                        'sort_order' => isset($optionData['order'][$optionId]) ? $optionData['order'][$optionId] : 0,
                    ];
                    $this->setup->getConnection()->insert($optionTable, $data);
                    $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);
                } else {
                    $data = [
                        'sort_order' => isset($optionData['order'][$optionId]) ? $optionData['order'][$optionId] : 0,
                    ];
                    $this->setup->getConnection()->update($optionTable, $data, ['option_id=?' => $intOptionId]);
                }

                foreach ($values as $storeId => $value) {
                    $select = $this->setup->getConnection()->select();
                    $select->from($optionValueTable, 'value_id')
                        ->where('option_id = ?', $intOptionId)
                        ->where('store_id = ?', $storeId)
                        ->where('value = ?', $value);

                    $valueId = $this->setup->getConnection()->fetchOne($select);
                    if ($valueId) {
                        $data = ['value' => $value];
                        $this->setup->getConnection()->update($optionValueTable, $data, ['value_id=?' => $valueId]);
                    } else {
                        $data = ['option_id' => $intOptionId, 'store_id' => $storeId, 'value' => $value];
                        $this->setup->getConnection()->insert($optionValueTable, $data);
                    }
                }
            }
        } elseif (isset($optionData['values'])) {
            foreach ($optionData['values'] as $sortOrder => $label) {
                // add option
                $data = ['attribute_id' => $optionData['attribute_id'], 'sort_order' => $sortOrder];
                $this->setup->getConnection()->insert($optionTable, $data);
                $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);

                $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $label];
                $this->setup->getConnection()->insert($optionValueTable, $data);
            }
        }
    }

    /**
     * Insert attribute and filter data
     *
     * @param array $data
     *
     * @return $this
     */
    private function _insertAttribute(array $data)
    {
        $bind = [];

        $fields = $this->_getAttributeTableFields();

        foreach ($data as $k => $v) {
            if (isset($fields[$k])) {
                $bind[$k] = $this->setup->getConnection()->prepareColumnValue($fields[$k], $v);
            }
        }
        if (!$bind) {
            return $this;
        }

        $this->setup->getConnection()->insert($this->setup->getTable('eav_attribute'), $bind);
        $id = $this->setup->getConnection()->lastInsertId($this->setup->getTable('eav_attribute'));
        unset($data['attribute_id']);
        $this->_insertAttributeAdditionalData(
            $data['entity_type_id'],
            array_merge(
                ['attribute_id' => $id],
                $data
            )
        );

        return $this;
    }

    /**
     * Insert attribute additional data
     *
     * @param int|string $entityTypeId
     * @param array $data
     *
     * @return $this
     */
    private function _insertAttributeAdditionalData($entityTypeId, array $data)
    {
        $additionalTable = $this->getEntityType(
            $entityTypeId,
            'additional_attribute_table'
        );
        if (!$additionalTable) {
            return $this;
        }
        $tableExists = $this->setup->getConnection()->isTableExists($this->setup->getTable($additionalTable));
        if ($additionalTable && $tableExists) {
            $bind = [];
            $fields = $this->setup->getConnection()->describeTable($this->setup->getTable($additionalTable));
            foreach ($data as $k => $v) {
                if (isset($fields[$k])) {
                    $bind[$k] = $this->setup->getConnection()->prepareColumnValue($fields[$k], $v);
                }
            }
            if (!$bind) {
                return $this;
            }
            $this->setup->getConnection()->insert(
                $this->setup->getTable($additionalTable),
                $bind
            );
        }

        return $this;
    }

    /**
     * @param string $groupName
     *
     * @return string
     * @since 100.1.0
     */
    public function convertToAttributeGroupCode($groupName)
    {
        $code = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($groupName)), '-');
        return $code == 'images' ? 'image-management' : $code;
    }

    /**
     * Retrieve attribute table fields
     *
     * @return array
     */
    private function _getAttributeTableFields()
    {
        return $this->setup->getConnection()->describeTable($this->setup->getTable('eav_attribute'));
    }
}
