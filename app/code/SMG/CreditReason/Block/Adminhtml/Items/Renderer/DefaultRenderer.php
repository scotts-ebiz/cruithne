<?php

namespace SMG\CreditReason\Block\Adminhtml\Items\Renderer;

use Psr\Log\LoggerInterface;
use SMG\CreditReason\Model\CreditReasonCodeFactory;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode as CreditReasonCodeReource;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode\CollectionFactory as CreditReasonCodeCollectionFactory;

class DefaultRenderer extends \Magento\Sales\Block\Adminhtml\Items\Renderer\DefaultRenderer
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var CreditReasonCodeFactory
     */
    protected $_creditReasonCodeFactory;

    /**
     * @var CreditReasonCodeReource
     */
    protected $_creditReasonCodeResource;

    /**
     * @var CreditReasonCodeCollectionFactory
     */
    protected $_creditReasonCodeCollectionFactory;

    /**
     * DefaultRenderer constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param LoggerInterface $logger
     * @param CreditReasonCodeFactory $creditReasonCodeFactory
     * @param CreditReasonCodeReource $creditReasonCodeResource
     * @param CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        LoggerInterface $logger,
        CreditReasonCodeFactory $creditReasonCodeFactory,
        CreditReasonCodeReource $_creditReasonCodeResource,
        CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory,
        array $data = []
    )
    {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $data);

        $this->_logger = $logger;
        $this->_creditReasonCodeFactory = $creditReasonCodeFactory;
        $this->_creditReasonCodeResource = $_creditReasonCodeResource;
        $this->_creditReasonCodeCollectionFactory = $creditReasonCodeCollectionFactory;
    }

    /**
     * Get the list of Credit Reason Codes
     *
     * @return CreditReasonCodeReource\Collection
     */
    public function getCreditReasonCodes()
    {
        // Get the list of active reason codes
        $creditResonCodes = $this->_creditReasonCodeCollectionFactory->create();
        $creditResonCodes->addFieldToFilter("is_active", ["eq" => true]);

        // return the reason codes
        return $creditResonCodes;
    }

    /**
     * Gets the Short Description of the desired reason code
     *
     * @return mixed|string
     */
    public function getCreditReasonCode()
    {
        // get the reason code
        $creditReasonCode = $this->getItem()->getData('refunded_reason_code');

        // Get the reason code to get the short description
        $creditReason = $this->_creditReasonCodeFactory->create();
        $this->_creditReasonCodeResource->load($creditReason, $creditReasonCode, 'reason_code');

        // get the short description and make sure it has a value
        $shortDescription = $creditReason->getData('short_desc');
        if (empty($shortDescription))
        {
            $shortDescription = 'No Reason Found';
        }

        // return the short description
        return $shortDescription;
    }
}