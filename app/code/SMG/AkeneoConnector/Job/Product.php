<?php

namespace SMG\AkeneoConnector\Job;

use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Magento\Catalog\Model\Product\Link;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Zend_Db_Expr as Expr;
/**
 * Class Product
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Product extends \Akeneo\Connector\Job\Product
{
    /**
     * Insert data into temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var bool $referenceEntitiesEnabled */
        $referenceEntitiesEnabled = $this->configHelper->isReferenceEntitiesEnabled();
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var int $index */
        $index = 0;
        /** @var mixed[] $filters */
        $filters = $this->getFilters($this->getFamily());
        /** @var mixed[] $metricsConcatSettings */
        $metricsConcatSettings = $this->configHelper->getMetricsColumns(null, true);
        /** @var string[] $metricSymbols */
        $metricSymbols = $this->getMetricsSymbols();
        /** @var string[] $attributeMetrics */
        $attributeMetrics = $this->attributeMetrics->getMetricsAttributes();
        /** @var string[] $referenceAttributes */
        $referenceAttributes = [];

        if ($referenceEntitiesEnabled) {
            $referenceAttributes = $this->getReferenceAttributesFromTmp();
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection */
        $regionCollection = $objectManager->create('Magento\Directory\Model\ResourceModel\Region\CollectionFactory')->create();
        $regionCollection->addCountryFilter('US');
        $optionsDictionary = [];
        foreach ($regionCollection->getItems() as $region) {
            $optionsDictionary[$region->getCode()] = $region->getId();
        }

        /** @var mixed[] $filter */
        foreach ($filters as $filter) {
            /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $products */
            $products = $this->akeneoClient->getProductApi()->all($paginationSize, $filter);

            $noDups = [];
            foreach ($products as $product) {
                if ( empty($product['values']['sync_to_magento'][0]['data']) OR  ! $product['values']['sync_to_magento'][0]['data']) {
                    continue;
                }
                if ( empty($product['values']['magento_sku'][0]['data']) ) {
                    continue;
                }
                $product['identifier'] = $product['values']['magento_sku'][0]['data'];
                if ( ! empty($product['values']['state_not_allowed'][0]['data']) ) {
                    foreach ($product['values']['state_not_allowed'][0]['data'] as $key => $value)
                        $product['values']['state_not_allowed'][0]['data'][$key] = $optionsDictionary[$value];
                }
                if (empty($noDups)) {
                    array_push($noDups, $product);
                } else {
                    $prodExists = false;
                    foreach ($noDups as $noDup) {
                        if ($product['identifier']  == $noDup['identifier']) {
                            if ($product['updated'] < $noDup['updated']) {
                                $prodExists = true;
                            }
                            break;
                        }
                    }
                    if (! $prodExists) {
                        array_push($noDups, $product);
                    }
                }
            }
            $products = $noDups;

            /**
             * @var mixed[] $product
             */
            foreach ($products as $product) {
                if ($referenceEntitiesEnabled) {
                    /** @var mixed[] $productReferenceAttributes */
                    $productReferenceAttributes = array_intersect_key($product['values'], $referenceAttributes);

                    /**
                     * @var string  $attributeCode
                     * @var mixed[] $productReferenceAttribute
                     */
                    foreach ($productReferenceAttributes as $attributeCode => $productReferenceAttribute) {
                        foreach ($productReferenceAttribute as $referenceAttribute) {
                            if (!isset($referenceAttribute['data']) || empty($referenceAttribute['data'])) {
                                continue;
                            }

                            /** @var mixed[] $referenceJsonData */
                            $referenceJsonData = $this->getReferenceJsonData(
                                $attributeCode,
                                $referenceAttribute['scope'],
                                $referenceAttribute['data']
                            );

                            if (isset($referenceAttribute['locale']) && $referenceAttribute['locale'] && $referenceAttribute['locale'] != '') {
                                foreach ($referenceJsonData as $keyLocalized => $referenceJsonDataLocalized) {
                                    foreach ($referenceJsonDataLocalized as $keyLocalizedData => $referenceJsonDataLocalizedData) {
                                        if ($referenceJsonDataLocalizedData['locale'] != $referenceAttribute['locale']) {
                                            unset($referenceJsonData[$keyLocalized][$keyLocalizedData]);
                                        }
                                    }
                                }
                            }

                            $product['values'] = array_merge_recursive($product['values'], $referenceJsonData);
                        }
                    }
                }

                /**
                 * @var string $attributeMetric
                 */
                foreach ($attributeMetrics as $attributeMetric) {
                    if (!isset($product['values'][$attributeMetric])) {
                        continue;
                    }

                    foreach ($product['values'][$attributeMetric] as $key => $metric) {
                        /** @var string|float $amount */
                        $amount = $metric['data']['amount'];
                        if ($amount != null) {
                            $amount = floatval($amount);
                        }

                        $product['values'][$attributeMetric][$key]['data']['amount'] = $amount;
                    }
                }

                /**
                 * @var mixed[] $metricsConcatSetting
                 */
                foreach ($metricsConcatSettings as $metricsConcatSetting) {
                    if (!isset($product['values'][$metricsConcatSetting])) {
                        continue;
                    }

                    /**
                     * @var int     $key
                     * @var mixed[] $metric
                     */
                    foreach ($product['values'][$metricsConcatSetting] as $key => $metric) {
                        /** @var string $unit */
                        $unit = $metric['data']['unit'];
                        /** @var string|false $symbol */
                        $symbol = array_key_exists($unit, $metricSymbols);

                        if (!$symbol) {
                            continue;
                        }

                        $product['values'][$metricsConcatSetting][$key]['data']['amount'] .= ' ' . $metricSymbols[$unit];
                    }
                }

                /** @var bool $result */
                $result = $this->entitiesHelper->insertDataFromApi($product, $this->getCode());

                if (!$result) {
                    $this->setMessage('Could not insert Product data in temp table');
                    $this->stop(true);

                    return;
                }

                $index++;
            }
        }

        // Remove declared file attributes columns if file import is disabled
        if (!$this->configHelper->isFileImportEnabled()) {
            /** @var array $attributesToImport */
            $attributesToImport = $this->configHelper->getFileImportColumns();

            if (!empty($attributesToImport)) {
                $attributesToImport = array_unique($attributesToImport);

                /** @var array $stores */
                $stores = array_merge(
                    $this->storeHelper->getStores(['lang']), // en_US
                    $this->storeHelper->getStores(['channel_code']), // channel
                    $this->storeHelper->getStores(['lang', 'channel_code']) // en_US-channel
                );

                /** @var AdapterInterface $connection */
                $connection = $this->entitiesHelper->getConnection();
                /** @var string $tmpTable */
                $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
                /** @var array $data */
                foreach ($attributesToImport as $attribute) {
                    if ($connection->tableColumnExists($tmpTable, $attribute)) {
                        $connection->dropColumn($tmpTable, $attribute);
                    }

                    // Remove scopable colums
                    foreach ($stores as $suffix => $storeData) {
                        if ($connection->tableColumnExists($tmpTable, $attribute . '-' . $suffix)) {
                            $connection->dropColumn($tmpTable, $attribute . '-' . $suffix);
                        }
                    }
                }
            }
        }

        if (empty($index)) {
            $this->setMessage('No Product data to insert in temp table');
            $this->stop(true);

            return;
        }

        $this->setMessage(__('%1 line(s) found', $index));
    }
    /**
     * Replace option code by id
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function updateOption()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string[] $except */
        $except = [
            'url_key',
            'country_of_manufacture',
            'state_not_allowed',
        ];
        $except = array_merge($except, $this->excludedColumns);

        /** @var string $column */
        foreach ($columns as $column) {
            if (in_array($column, $except) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!$connection->tableColumnExists($tmpTable, $column)) {
                continue;
            }

            /** @var string[] $columnParts */
            $columnParts = explode('-', $column, 2);
            /** @var string $columnPrefix */
            $columnPrefix = reset($columnParts);
            $columnPrefix = sprintf('%s-', $columnPrefix);
            /** @var int $prefixLength */
            $prefixLength = strlen($columnPrefix) + 1;
            /** @var string $entitiesTable */
            $entitiesTable = $this->entitiesHelper->getTable('akeneo_connector_entities');

            // Sub select to increase performance versus FIND_IN_SET
            /** @var Select $subSelect */
            $subSelect = $connection->select()->from(
                ['c' => $entitiesTable],
                ['code' => sprintf('SUBSTRING(`c`.`code`, %s)', $prefixLength), 'entity_id' => 'c.entity_id']
            )->where(sprintf('c.code LIKE "%s%s"', $columnPrefix, '%'))->where('c.import = ?', 'option');

            // if no option no need to continue process
            if (!$connection->query($subSelect)->rowCount()) {
                continue;
            }

            //in case of multiselect
            /** @var string $conditionJoin */
            $conditionJoin = "IF ( locate(',', `" . $column . "`) > 0 , " . new Expr(
                    "FIND_IN_SET(`c1`.`code`,`p`.`" . $column ."`) > 0"
                ) . ", `p`.`" . $column . "` = `c1`.`code` )";

            /** @var Select $select */
            $select = $connection->select()->from(
                ['p' => $tmpTable],
                ['identifier' => 'p.identifier', 'entity_id' => 'p._entity_id']
            )->joinInner(
                ['c1' => new Expr('(' . (string)$subSelect . ')')],
                new Expr($conditionJoin),
                [$column => new Expr('GROUP_CONCAT(`c1`.`entity_id` SEPARATOR ",")')]
            )->group('p.identifier');

            /** @var string $query */
            $query = $connection->insertFromSelect(
                $select,
                $tmpTable,
                ['identifier', '_entity_id', $column],
                AdapterInterface::INSERT_ON_DUPLICATE
            );

            $connection->query($query);
        }
    }

}
