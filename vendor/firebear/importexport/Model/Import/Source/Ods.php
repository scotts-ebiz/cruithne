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

/**
 * Ods Import Adapter
 */
class Ods extends AbstractSource
{
    use ImportMap;
	
    /**
     * Row Iterator
     *
     * @var \Box\Spout\Reader\ODS\RowIterator
     */     
    protected $rowIterator;
    
    /**
     * Spreadsheet Reader
     *
     * @var \Box\Spout\Reader\ODS\Reader
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
    protected $extension = 'ods';
    
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
		$alias = 'Box\Spout\Reader\ODS\RowIterator';
		$original = 'Firebear\ImportExport\Model\Import\Source\Spout\Ods\RowIterator';
		class_alias($original, $alias);
		
		$alias = 'Box\Spout\Reader\ODS\Helper\CellValueFormatter';
		$original = 'Firebear\ImportExport\Model\Import\Source\Spout\Ods\Helper\CellValueFormatter';
		class_alias($original, $alias);
		
		$file = $directory->getAbsolutePath($file);
		$this->reader = ReaderFactory::create(Type::ODS);
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
		$this->_key = 0;
        $this->_row = [];		
        if (!$this->_colQty) {
			// Because sheet and row data is located in the file, we can't rewind both the
			// sheet iterator and the row iterator, as XML file cannot be read backwards.
			// Therefore, rewinding the row iterator has been disabled.
			// @see Box\Spout\Reader\ODS\RowIterator
			$this->rowIterator->rewind(); 
        }		
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
 
