<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Source;

use Magento\Framework\Filesystem\Directory\Read as DirectoryRead;
use Magento\ImportExport\Model\Import\AbstractSource;
use Firebear\ImportExport\Exception\XmlException as FirebearXmlException;

/**
 * XML Import Adapter
 */
class Xml extends AbstractSource
{

    use \Firebear\ImportExport\Traits\Import\Map;

    const CREATE_ATTRIBUTE = 'create_attribute';

    /**
     * @var XMLReader
     */
    protected $reader;

    private $lastRead;

    private $elementStack;

    protected $maps;

    protected $extension = 'xml';

    protected $mimeTypes = [
        'text/xml',
          'text/plain',
        'application/excel',
        'application/xml',
        'application/vnd.ms-excel',
        'application/vnd.msexcel'
    ];


    protected $platform;

    /**
     * Iterator Lock Flag
     *
     * @var bool
     */
    protected $_lock = false;

    /**
     * Prepared Items
     *
     * @var array
     */
    protected $_items = [];


    /**
     * Initialize Adapter
     *
     * @param array $file
     * @param DirectoryRead $directory
     * @throws \Exception
     * @throws FirebearXmlException
     */
    public function __construct(
        $file,
        DirectoryRead $directory
    ) {
        $filePath = $file;
        if (0 !== strpos($file, $directory->getAbsolutePath())) {
            $filePath =  $directory->getAbsolutePath($file);
        }

        $result = $this->checkMimeType($filePath);

        if ($result !== true) {
            throw new \Exception($result);
        }

        libxml_use_internal_errors(true);
        $this->reader = simplexml_load_file(
            $filePath,
            "SimpleXMLIterator"
        );

        if (false === $this->reader) {
            throw new FirebearXmlException(libxml_get_errors());
        }

        $this->reader->rewind();

        $this->getColumns();
    }

    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        $this->reader->close();
    }

    /**
     * Read next item from XML-file
     *
     * @return array
     */
    protected function _getNextRow()
    {
        $parsed = [];
        /* reader prepared items */
        if ($this->_lock) {
            foreach ($this->_items as $field => $items) {
                foreach ($items as $key => $item) {
                    $parsed[$field] = $item;
                    unset($this->_items[$field][$key]);
                    break;
                }
                if (0 == count($this->_items[$field])) {
                    unset($this->_items[$field]);
                }
                break;
            }
            if (0 == count($this->_items)) {
                $this->_items = [];
                $this->_lock = false;
            }
            return $parsed;
        } elseif ($this->reader->hasChildren()) {
            foreach ($this->reader->getChildren() as $name => $data) {
                if ($name == self::CREATE_ATTRIBUTE) {
                    $text = 'attribute';
                    $valueText = '';
                    foreach ($data as $nameAttribute => $valueAttribute) {
                        if ($nameAttribute != 'value') {
                            $text .= "|" . $nameAttribute . ":" . (string)$valueAttribute;
                        } else {
                            $valueText = (string)$valueAttribute;
                        }
                    }
                    $parsed[$text] = $valueText;
                    continue;
                } elseif (isset($data->item)) { /* prepare child children */
                    $key = (string)$name;
                    $this->_items[$key] = [];
                    foreach ($data->item as $itemField) {
                        $item = [];
                        foreach ($itemField as $field => $fieldValue) {
                            $field = (string)$field;
                            if (isset($fieldValue->item)) {
                                $subKey = $key . '_' . $field;
                                $this->_items[$subKey] = [];
                                foreach ($fieldValue->item as $subItemField) {
                                    $subItem = [];
                                    foreach ($subItemField as $subField => $subFieldValue) {
                                        $subItem[(string)$subField] = (string)$subFieldValue;
                                    }
                                    $this->_items[$subKey][] = $subItem;

                                }
                                $item[$field] = '';
                            } else {
                                $item[$field] = (string)$fieldValue;
                            }
                        }
                        $this->_items[$key][] = $item;
                    }
                    $value = '';
                } else {
                    $value = (string)$data;
                    if (strpos($value, "'") !== false) {
                        $this->_foundWrongQuoteFlag = true;
                    }
                }
                $parsed[$name] = $value;
            }
            $this->_lock = (0 < count($this->_items)) ? true : false;
        }
        return $parsed;
    }

    /**
     * Return Columns List
     *
     * @return array
     */
    protected function getColumns()
    {
        for ($this->reader->rewind(); $this->reader->valid(); $this->reader->next()) {
            $colNames = array_keys($this->_getNextRow());
            if (count(array_unique($colNames)) != count($colNames)) {
                throw new \InvalidArgumentException('Duplicates found in column names: ' . var_export($colNames, 1));
            }
            $diffArray = array_diff($colNames, $this->_colNames);
            $this->_colNames = array_merge($this->_colNames, $diffArray);
            $this->_colQty = count($this->_colNames);
        }
//        if (empty($this->_colNames)) {
//            throw new \InvalidArgumentException('Empty column names');
//        }
    }

    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     */
    public function rewind()
    {
        $this->_key = -1;
        $this->_row = [];
        $this->_items = [];
        $this->_lock = false;
        $this->reader->rewind();
        $row = $this->_getNextRow();
        $this->_row = $row;
    }

    /**
     * Return the current element
     *
     * @return array
     */
    public function current()
    {
        $row = $this->_row;
        $diffArray = array_diff($this->_colNames, array_keys($row));
        if (count($diffArray)) {
            foreach ($diffArray as $name) {
                $row[$name] = '';
            }
        }
        return $this->replaceValue($this->changeFields($row));
    }

    /**
     * Move forward to next element (\Iterator interface)
     *
     * @return void
     */
    public function next()
    {
        if (!$this->_lock) {
            $this->reader->next();
        }
        parent::next();
    }

    /**
     * Checks if current position is valid (\Iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
        return $this->_lock ? true : $this->reader->valid();
    }

    /**
     * @return mixed
     */
    public function getColNames()
    {
        return $this->replaceColumns($this->_colNames);
    }

    /**
     * @param $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
