<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 5/14/19
 * Time: 9:01 AM
 */

namespace SMG\CreditReason\Block\Adminhtml\Items;

use Magento\Framework\Serialize\Serializer\Json;
use SMG\CreditReason\Helper\CreditReasonHelper;

class Renderer extends \Magento\Bundle\Block\Adminhtml\Sales\Order\Items\Renderer
{
    /**
     * @var CreditReasonHelper
     */
    protected $_creditReasonHelper;

    /**
     * Renderer constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param CreditReasonHelper $creditReasonHelper
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        CreditReasonHelper $creditReasonHelper,
        array $data = [],
        Json $serializer = null)
    {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $data, $serializer);

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