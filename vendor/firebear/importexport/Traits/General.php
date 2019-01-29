<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits;

use Magento\ImportExport\Model\Import;
use Symfony\Component\Console\Output\OutputInterface;

trait General
{
    /**
     * @return mixed
     */
    public function getDuplicateFields()
    {
        return $this->duplicateFields;
    }

    /**
     * @param $logger
     *
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @param $errorAggregator
     */
    public function setErrorAggregator($errorAggregator)
    {
        return $this->errorAggregator = $errorAggregator;
    }

    /**
     * import product data
     */
    public function importDataPart($file, $offset, $job)
    {
        $this->setDataSourceData(
            $file,
            $job,
            $offset
        );

        $this->importData();

        return true;
    }

    public function setDataSourceData($file, $job, $offset)
    {
        if (!preg_match('/^[0-9-]+$/', $file)) {
            return;
        }
        $this->_dataSourceModel->setFile($file);
        $this->_dataSourceModel->setJob((int) $job);
        $this->_dataSourceModel->setOffset((int) $offset);
    }

    /**
     * @param int $saveBunches
     *
     * @return mixed
     */
    public function validateData($saveBunches = 1)
    {
        if (isset($this->_parameters['output'])) {
            $this->output = $this->_parameters['output'];
        }

        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            // do all permanent columns exist?
            $absentColumns = array_diff($this->_permanentAttributes, $this->getSource()->getColNames());
            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);

            if (Import::BEHAVIOR_DELETE != $this->getBehavior()) {
                // check attribute columns names validity
                $columnNumber = 0;
                $emptyHeaderColumns = [];
                $invalidColumns = [];
                $invalidAttributes = [];
                foreach ($this->getSource()->getColNames() as $columnName) {
                    $this->addLogWriteln(__('Checked column %1', $columnNumber), $this->output);
                    $isNewAttribute = true;
                    $columnNumber++;
                    if (!$this->isAttributeParticular($columnName)) {
                        if (trim($columnName) == '') {
                            $emptyHeaderColumns[] = $columnNumber;
                        } elseif (!preg_match('/^[a-z][a-z0-9_\:]*$/', $columnName)) {
                            $invalidColumns[] = $columnName;
                        } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
                            $invalidAttributes[] = $columnName;
                        }
                    }
                }

                $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
                $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
                $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);
                $this->addLogWriteln(__('Finish checking columns'), $this->output);
                $this->addLogWriteln(
                    __('Errors count: %1', $this->getErrorAggregator()->getErrorsCount()),
                    $this->output
                );
            }

            if (!$this->getErrorAggregator()->getErrorsCount()) {
                if ($saveBunches) {
                    $this->addLogWriteln(__('Start saving bunches'), $this->output);
                    $this->_saveValidatedBunches();
                    $this->addLogWriteln(__('Finish saving bunches'), $this->output);
                }
                $this->_dataValidated = true;
            }
        }
        return $this->getErrorAggregator();
    }

    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     *
     * @return $this
     */
    public function addLogWriteln($debugData, OutputInterface $output = null, $type = null)
    {
        $text = $debugData;
        if ($debugData instanceof \Magento\Framework\Phrase) {
            $text = $debugData->__toString();
        }

        switch ($type) {
            case 'error':
                $this->_logger->error($text);
                break;
            case 'warning':
                $this->_logger->warning($text);
                break;
            case 'debug':
                $this->_logger->debug($text);
                break;
            default:
                $this->_logger->info($text);
        }


        if ($output) {
            switch ($type) {
                case 'error':
                    $text = '<error>' . $text . '</error>';
                    break;
                case 'info':
                    $text = '<info>' . $text . '</info>';
                    break;
                default:
                    $text = '<comment>' . $text . '</comment>';
                    break;
            }
            $output->writeln($text, $output->getVerbosity());
        }

        return $this;
    }

    public function setErrorMessages()
    {
        return true;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function joinIdenticalyData($data)
    {
        $reverts = [];
        foreach ($this->_parameters['map'] as $item) {
            if ($item['import']) {
                $reverts[$item['import']][] = $item['system'];
            }
        }
        if (!empty($this->_parameters['identicaly'])) {
            foreach ($this->_parameters['identicaly'] as $elem) {
                $data[$elem['system']] = $data[$reverts[$elem['import']][0]];
            }
        }

        return $data;
    }

    public function customChangeData($data)
    {
        return $data;
    }

    public function customBunchesData($data)
    {
        return $data;
    }

    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Apply filter to collection and add not skipped attributes to select.
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     *
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareEntityCollection(\Magento\Eav\Model\Entity\Collection\AbstractCollection $collection)
    {
        if (!isset(
                $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP]
            ) || !is_array(
                $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP]
            )
        ) {
            $filter = [];
        } else {
            $filter = $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP];
        }

        $exportCodes = $this->_getExportAttrCodes();

        foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
            $attrCode = $attribute->getAttributeCode();

            // filter applying
            if (isset($filter[$attrCode])) {
                $attrFilterType = \Magento\ImportExport\Model\Export::getAttributeFilterType($attribute);

                if (\Magento\ImportExport\Model\Export::FILTER_TYPE_SELECT == $attrFilterType) {
                    if (is_scalar($filter[$attrCode])) {
                        if ($filter[$attrCode] == 0) {
                            $collection->addAttributeToFilter([
                                ['attribute' => $attrCode, 'eq' => $filter[$attrCode]],
                                ['attribute' => $attrCode, 'null' => 1],
                            ]);
                        } else {
                            $collection->addAttributeToFilter($attrCode, ['eq' => $filter[$attrCode]]);
                        }
                    }
                } elseif (\Magento\ImportExport\Model\Export::FILTER_TYPE_INPUT == $attrFilterType) {
                    if (is_scalar($filter[$attrCode]) && trim($filter[$attrCode])) {
                        $collection->addAttributeToFilter($attrCode, ['like' => "%{$filter[$attrCode]}%"]);
                    }
                } elseif (\Magento\ImportExport\Model\Export::FILTER_TYPE_DATE == $attrFilterType) {
                    if (is_array($filter[$attrCode]) && count($filter[$attrCode]) == 2) {
                        $from = array_shift($filter[$attrCode]);
                        $to = array_shift($filter[$attrCode]);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new \DateTime($from))->format('m/d/Y');
                            $collection->addAttributeToFilter($attrCode, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new \DateTime($to))->format('m/d/Y');
                            $collection->addAttributeToFilter($attrCode, ['to' => $date, 'date' => true]);
                        }
                    }
                } elseif (\Magento\ImportExport\Model\Export::FILTER_TYPE_NUMBER == $attrFilterType) {
                    if (is_array($filter[$attrCode]) && count($filter[$attrCode]) == 2) {
                        $from = array_shift($filter[$attrCode]);
                        $to = array_shift($filter[$attrCode]);

                        if (is_numeric($from)) {
                            $collection->addAttributeToFilter(
                                $attrCode,
                                ['from' => $from]
                            );
                        }
                        if (is_numeric($to)) {
                            $collection->addAttributeToFilter(
                                $attrCode,
                                ['to' => $to]
                            );
                        }
                    }
                }
            }
            if (in_array($attrCode, $exportCodes)) {
                $collection->addAttributeToSelect($attrCode);
            }
        }
        return $collection;
    }
}
