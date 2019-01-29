<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Eav\Model\Config;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Psr\Log\LoggerInterface;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as DataSourceModel;

/**
 * Import Adapter Context
 */
class Context
{
    /**
     * Json Helper
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;
    
    /**
     * Import Export Data
     *
     * @var \Magento\ImportExport\Helper\Data
     */
    protected $importExportData;
    
    /**
     * DB Data Source Model
     *
     * @var \Firebear\ImportExport\Model\ResourceModel\Import\Data
     */
    protected $dataSourceModel; 
    
    /**
     * Eav Model Config
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $config;
    
    /**
     * Resource Connection
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;
    
    /**
     * Resource Helper
     *
     * @var \Magento\ImportExport\Model\ResourceModel\Helper
     */
    protected $resourceHelper; 
    
    /**
     * String Lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;
    
    /**
     * Processing Error Aggregator
     *
     * @var \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface
     */
    protected $errorAggregator;    
    
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;  
	
    /**
     * Initialize Context
     *
     * @param JsonHelper $jsonHelper
     * @param ImportExportData $importExportData
     * @param DataSourceModel $dataSourceModel 
     * @param Config $config
     * @param ResourceConnection $resource  
     * @param ResourceHelper $resourceHelper
     * @param StringUtils $string 
     * @param ProcessingErrorAggregatorInterface $errorAggregator     
     * @param Logger $logger   
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportExportData $importExportData,
        DataSourceModel $dataSourceModel,
        Config $config,
        ResourceConnection $resource,
        ResourceHelper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        LoggerInterface $logger	
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->importExportData = $importExportData;
        $this->dataSourceModel = $dataSourceModel;
        $this->config = $config;
        $this->resource = $resource;
        $this->resourceHelper = $resourceHelper;
        $this->string = $string;
        $this->errorAggregator = $errorAggregator;
        $this->logger = $logger;		
    }
    
    /**
     * Retrieve Json Helper
     *
     * @return \Magento\Framework\Json\Helper\Data
     */
    public function getJsonHelper()
    {
        return $this->jsonHelper;
    }
    
    /**
     * Retrieve Import Export Data
     *
     * @return \Magento\ImportExport\Helper\Data
     */
    public function getImportExportData()
    {
        return $this->importExportData;
    }
    
    /**
     * Retrieve Data Source Model
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getDataSourceModel()
    {
        return $this->dataSourceModel;
    }
    
    /**
     * Retrieve Eav Model Config
     *
     * @return \Magento\Eav\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Retrieve Resource Connection
     *
     * @return \Magento\Framework\App\ResourceConnection
     */
    public function getResource()
    {
        return $this->resource;
    }
    
    /**
     * Retrieve Resource Helper
     *
     * @return \Magento\ImportExport\Model\ResourceModel\Helper
     */
    public function getResourceHelper()
    {
        return $this->resourceHelper;
    }
    
    /**
     * Retrieve String Lib
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getStringUtils()
    {
        return $this->string;
    }
    
    /**
     * Retrieve Processing Error Aggregator
     *
     * @return \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator()
    {
        return $this->errorAggregator;
    }
    
    /**
     * Retrieve Logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }	    
}
