<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Renderer;

use Psr\Log\LoggerInterface;
use SMG\CreditReason\Model\CreditReasonCodeFactory;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode as CreditReasonCodeReource;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode\CollectionFactory as CreditReasonCodeCollectionFactory;

/**
 * Custom Reason field renderer
 */
class Reason extends \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Renderer\Reason
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
     * @var CreditReasonCodeResource
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
                                LoggerInterface $logger,
                                CreditReasonCodeFactory $creditReasonCodeFactory,
                                CreditReasonCodeReource $_creditReasonCodeResource,
                                CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory,
                                array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_logger = $logger;
        $this->_creditReasonCodeFactory = $creditReasonCodeFactory;
        $this->_creditReasonCodeResource = $_creditReasonCodeResource;
        $this->_creditReasonCodeCollectionFactory = $creditReasonCodeCollectionFactory;
    }

    /**
     * Get the list of Credit Reason Codes
     *
     * @return CreditReasonCodeResource  Collection
     */
    public function getCreditReasonCodes()
    {
        // Get the list of active reason codes
        $creditReasonCodes = $this->_creditReasonCodeCollectionFactory->create();
        $creditReasonCodes->addFieldToFilter("is_active", ["eq" => true]);

        // return the reason codes
        return $creditReasonCodes;
    }

    /**
     * Gets the Short Description of the desired reason code
     *
     * @return mixed|string
     */
    public function getCreditReasonCode()
    {

        // May use wto display Reason Code in admin in future


//        // get the reason code
//        $creditReasonCode = $this->getItem()->getData('refunded_reason_code');
//
//        // Get the reason code to get the short description
//        $creditReason = $this->_creditReasonCodeFactory->create();
//        $this->_creditReasonCodeResource->load($creditReason, $creditReasonCode, 'reason_code');
//
//        // get the short description and make sure it has a value
//        $shortDescription = $creditReason->getData('short_desc');
//        if (empty($shortDescription))
//        {
//            $shortDescription = 'No Reason Found';
//        }
//
//        // return the short description
//        return $shortDescription;
    }
}