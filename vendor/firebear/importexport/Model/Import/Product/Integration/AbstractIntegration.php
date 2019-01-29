<?php
/**
 * AbstractIntegration
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Symfony\Component\Console\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;

abstract class AbstractIntegration
{
    use \Firebear\ImportExport\Traits\General;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface  */
    protected $productRepository;
    /** @var \Magento\Framework\App\ObjectManager  */
    protected $objectManager;
    /** @var Data */
    protected $_dataSourceModel;
    /** @var \Symfony\Component\Console\Output\ConsoleOutput  */
    protected $output;
    /** @var \Psr\Log\LoggerInterface  */
    protected $_logger;
    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockItem;

    /**
     * AbstractIntegration constructor.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Data $_dataSourceModel
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockItem
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ObjectManager $objectManager,
        Data $_dataSourceModel,
        ConsoleOutput $output,
        LoggerInterface $logger,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem
    ) {
        $this->productRepository = $productRepository;
        $this->objectManager = $objectManager;
        $this->_dataSourceModel = $_dataSourceModel;
        $this->output = $output;
        $this->_logger = $logger;
        $this->stockItem = $stockItem;
    }

    /**
     * @return Data
     */
    public function getDataSourceModel(): Data
    {
        return $this->_dataSourceModel;
    }

    /**
     * @param Data $dataSourceModel
     *
     * @return Data
     */
    public function setDataSourceModel(Data $dataSourceModel): Data
    {
        return $this->_dataSourceModel = $dataSourceModel;
    }

    /**
     * @return \Magento\Catalog\Api\ProductRepositoryInterface
     */
    public function getProductRepository(): ProductRepositoryInterface
    {
        return $this->productRepository;
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }

    /**
     * @param string|bool $verbosity
     * @return mixed
     */
    abstract public function importData($verbosity = false);
}
