<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Customer;

class Finance extends \Magento\ImportExport\Model\Export\AbstractEntity
{
    use \Firebear\ImportExport\Traits\Export\Entity;

    use \Firebear\ImportExport\Traits\General;

    /**#@+
     * Permanent column names
     *
     * Names that begins with underscore is not an attribute. This name convention is for
     * to avoid interference with same attribute name.
     */
    const COLUMN_EMAIL = '_email';

    const COLUMN_FINANCE_WEBSITE = '_finance_website';

    const COLUMN_WEBSITE = '_website';

    /**#@-*/

    /**
     * Attribute collection name
     * @codingStandardsIgnoreStart
     */
    const ATTRIBUTE_COLLECTION_NAME = \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection::class;
    /**
     * @codingStandardsIgnoreEnd
     */

    /**
     * Website ID-to-code
     *
     * @var array
     */
    protected $_websiteIdToCode = [];

    /**
     * XML path to page size parameter
     */
    const XML_PATH_PAGE_SIZE = 'export/customer_page_size/finance';

    /**
     * Array of attributes for export
     *
     * @var string[]
     */
    protected $_entityAttributes;

    /**
     * Customers whose address are exported
     *
     * @var \Magento\CustomerFinance\Model\ResourceModel\Customer\Collection
     */
    protected $_customerCollection;

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_permanentAttributes = [self::COLUMN_EMAIL, self::COLUMN_WEBSITE, self::COLUMN_FINANCE_WEBSITE];

    /**
     * Customers whose financial data is exported
     *
     * @var \Magento\CustomerImportExport\Model\Export\Customer
     */
    protected $_customerEntity;

    /**
     * @var \Magento\CustomerFinance\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * Helper to check whether modules are enabled/disabled
     *
     * @var \Magento\CustomerFinance\Helper\Data
     */
    protected $_customerFinanceData;

    /**
     * @var \Magento\CustomerImportExport\Model\Export\CustomerFactory
     */
    protected $_eavCustomerFactory;

    /**
     * @var \Firebear\ImportExport\Model\IntegrationFactory
     */
    protected $_integrationFactory;

    /**
     * Finance constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\ImportExport\Model\Export\Factory $collectionFactory
     * @param \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $resourceColFactory
     * @param \Magento\CustomerImportExport\Model\Export\CustomerFactory $eavCustomerFactory
     * @param \Firebear\ImportExport\Model\IntegrationFactory $integrationFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ImportExport\Model\Export\Factory $collectionFactory,
        \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $resourceColFactory,
        \Magento\CustomerImportExport\Model\Export\CustomerFactory $eavCustomerFactory,
        \Firebear\ImportExport\Model\IntegrationFactory $integrationFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $storeManager, $collectionFactory, $resourceColFactory, $data);

        $this->_eavCustomerFactory = $eavCustomerFactory;
        $this->_integrationFactory = $integrationFactory;

        $this->_initFrontendWebsites()->_initWebsites(true);
        $this->setFileName($this->getEntityTypeCode());
    }

    /**
     * @return mixed
     */
    protected function _getHeaderColumns()
    {
        $headers = array_merge($this->getPermanentAttributes(), $this->_getExportAttributeCodes());

        return $this->changeHeaders($headers);
    }

    /**
     * Initialize frontend websites
     *
     * @return $this
     */
    protected function _initFrontendWebsites()
    {
        /** @var $website \Magento\Store\Model\Website */
        foreach ($this->_storeManager->getWebsites() as $website) {
            $this->_websiteIdToCode[$website->getId()] = $website->getCode();
        }
        return $this;
    }

    /**
     * Get customers collection
     *
     * @return \Magento\CustomerFinance\Model\ResourceModel\Customer\Collection
     */
    protected function _getEntityCollection()
    {
        if (empty($this->_customerCollection)) {
            if (empty($this->_customerCollectionFactory)) {
                $this->_customerCollectionFactory = $this->_integrationFactory->create('Magento\CustomerFinance\Model\ResourceModel\Customer\CollectionFactory');
            }
            $this->_customerCollection = $this->_customerCollectionFactory->create();
        }
        return $this->_customerCollection;
    }

    /**
     * Set parameters (push filters from post into export customer model)
     *
     * @param string[] $parameters
     * @return \Magento\CustomerImportExport\Model\Export\Address
     */
    public function setParameters(array $parameters)
    {
        if (empty($this->_customerEntity)) {
            $this->_customerEntity = $this->_eavCustomerFactory->create();
        }

        if (empty($this->_customerFinanceData)) {
            $this->_customerFinanceData = $this->_integrationFactory->create('Magento\CustomerFinance\Helper\Data');
        }

        if ($this->_customerFinanceData->isCustomerBalanceEnabled()) {
            $this->_getEntityCollection()->joinWithCustomerBalance(
                $this->_customerEntity->getAttributeCollection(),
                $this->getAttributeCollection()
            );
        }

        if ($this->_customerFinanceData->isRewardPointsEnabled()) {
            $this->_getEntityCollection()->joinWithRewardPoints(
                $this->_customerEntity->getAttributeCollection(),
                $this->getAttributeCollection()
            );
        }

        $this->_customerFinanceData->populateParams($parameters);
        $this->_customerEntity->setParameters($parameters);
        $this->_customerEntity->filterEntityCollection($this->_getEntityCollection());

        return parent::setParameters($parameters);
    }

    /**
     * Get list of permanent attributes
     *
     * @return string[]
     */
    public function getPermanentAttributes()
    {
        return $this->_permanentAttributes;
    }

    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        return array_unique(
            array_merge($this->getPermanentAttributes(), $this->_getExportAttributeCodes())
        );
    }

    public function export()
    {
        $writer = $this->getWriter();

        // create export file
        $writer->setHeaderCols($this->_getHeaderColumns());
        $this->_exportCollectionByPages($this->_getEntityCollection());

        return [$writer->getContents(), $this->_getEntityCollection()->getSize()];
    }

    /**
     * @param $item
     */
    public function exportItem($item)
    {
        $validAttributeCodes = $this->_getExportAttributeCodes();

        foreach ($this->_websiteIdToCode as $websiteCode) {
            $row = [];
            foreach ($validAttributeCodes as $code) {
                $attributeCode = $websiteCode . '_' . $code;
                $websiteData = $item->getData($attributeCode);
                if (null !== $websiteData) {
                    $row[$code] = $websiteData;
                }
            }

            if (!empty($row)) {
                $row[self::COLUMN_EMAIL] = $item->getEmail();
                $row[self::COLUMN_WEBSITE] = $this->_websiteIdToCode[$item->getWebsiteId()];
                $row[self::COLUMN_FINANCE_WEBSITE] = $websiteCode;
                $this->getWriter()->writeRow($this->changeRow($row));
            }
        }
    }

    /**
     * Entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'customer_finance';
    }
}
