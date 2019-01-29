<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Source;

use Magento\Framework\Filesystem\Directory\Read as Directory;
use Magento\ImportExport\Model\Import\AbstractSource;
use Firebear\ImportExport\Traits\Import\Map as ImportMap;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;
use Magento\ImportExport\Model\Import\AbstractEntity;

/**
 * Xlsx Import Adapter
 */
class Xlsx extends AbstractSource
{
    use ImportMap;
	
    /**
     * Row Iterator
     *
     * @var \Box\Spout\Reader\XLSX\RowIterator
     */     
    protected $rowIterator;
    
    /**
     * Spreadsheet Reader
     *
     * @var \Box\Spout\Reader\XLSX\Reader
     */     
    protected $reader;
    
    /**
     * Column Map
     *
     * @var array
     */     
    protected $maps = [];
    
    /**
     * Office Document File Extension
     *
     * @var array
     */       
    protected $extension = 'xlsx';
    
    /**
     * Platform
     *
     * @var mixed
     */    
    protected $platform;
    
    /**
     * Initialize Adapter
     * 
     * @param array $file
     * @param Directory $directory
     */
    public function __construct(
        $file,
        Directory $directory
    ) {
		$file = $directory->getAbsolutePath($file);
		$this->reader = ReaderFactory::create(Type::XLSX);
		$this->reader->setShouldFormatDates(true);
		$this->reader->open($file);
		
		$sheetIterator = $this->reader->getSheetIterator();
		$sheetIterator->rewind();
		$sheet = $sheetIterator->current();
		
		$this->rowIterator = $sheet->getRowIterator();
		
		register_shutdown_function([$this, 'destruct']);
		
		$this->rewind();
		parent::__construct(
			$this->_getNextRow()
		);
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
		$this->rowIterator->rewind(); 
        if ($this->_colQty) {
			$this->next();
        }
    }
    
    /**
     * Move forward to next element (\Iterator interface)
     *
     * @return void
     */
    public function next()
    {
        $this->_key++;
		$this->rowIterator->next();
        $row = $this->_getNextRow();
        if (false === $row || [] === $row) {
            $this->_row = [];
            $this->_key = -1;
        } else {
            $this->_row = $row;
        }
    }
    
    /**
     * Return the key of the current element (\Iterator interface)
     *
     * @return int -1 if out of bounds, 0 or more otherwise
     */
    public function key()
    {
		return $this->_key;
    }

    public function current()
    {
        $row = $this->rowIterator->current();
        if (count($row) != $this->_colQty) {
            if ($this->_foundWrongQuoteFlag) {
                throw new \InvalidArgumentException(AbstractEntity::ERROR_CODE_WRONG_QUOTES);
            } else {
                throw new \InvalidArgumentException(AbstractEntity::ERROR_CODE_COLUMNS_NUMBER);
            }
        }
        $array = array_combine($this->_colNames, $row);

        $array = $this->replaceValue($this->changeFields($array));

        return $array;
    }
    
    /**
     * Checks if current position is valid (\Iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
		return -1 !== $this->_key && $this->rowIterator->valid();
    }
    
    /**
     * Render next row
     *
     * @return array|false
     */
    protected function _getNextRow()
    {
		return $this->rowIterator->current();
    }
    
    /**
     * Column names getter
     *
     * @return array
     */
    public function getColNames()
    {
		return $this->replaceColumns($this->_colNames);
    }
    
    /**
     * Set Platform
     *
     * @param $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * Return Platform
     *
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }
	
    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        if (is_object($this->reader)) {
            $this->reader->close();
        }
    }
}
 
