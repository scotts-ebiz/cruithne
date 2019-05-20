<?php

namespace SMG\CreditReason\Block\Adminhtml\Items\Renderer;

use SMG\CreditReason\Helper\CreditReasonHelper;

class DefaultRenderer extends \Magento\Sales\Block\Adminhtml\Items\Renderer\DefaultRenderer
{
    /**
     * @var CreditReasonHelper
     */
    protected $_creditReasonHelper;

    /**
     * DefaultRenderer constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param CreditReasonHelper $creditReasonHelper
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        CreditReasonHelper $creditReasonHelper,
        array $data = [])
    {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $data);

        $this->_creditReasonHelper = $creditReasonHelper;
    }

    /**
     * Get the list of Credit Reason Codes
     *
     * @return \SMG\CreditReason\Model\ResourceModel\CreditReasonCode\Collection
     */
    public function getCreditReasonCodes()
    {
        // return the reason codes
        return $this->_creditReasonHelper->getCreditReasonCodes();
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

        // return the short description
        return $this->_creditReasonHelper->getCreditReasonCode($creditReasonCode);
    }
}