<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\OrderReason\Block\Adminhtml\Order\Create\Items;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use SMG\CreditReason\Model\CreditReasonCodeFactory;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode as CreditReasonCodeReource;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode\CollectionFactory as CreditReasonCodeCollectionFactory;
/**
 * Adminhtml sales order create items grid block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Grid extends \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid
{
    /**
     * @var _creditReasonCodeFactory
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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\GiftMessage\Model\Save $giftMessageSave
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\GiftMessage\Helper\Message $messageHelper
     * @param StockRegistryInterface $stockRegistry
     * @param StockStateInterface $stockState
     * @param OrderCustomDiscountFactory $orderCustomDiscountFactory
     * @param orderCustomDiscountResource $orderCustomDiscountResource
     * @param OrderCustomDiscountCollectionFactory $orderCustomDiscountCollectionFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\GiftMessage\Model\Save $giftMessageSave,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\GiftMessage\Helper\Message $messageHelper,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        CreditReasonCodeFactory $creditReasonCodeFactory,
        CreditReasonCodeReource $_creditReasonCodeResource,
        CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory,
        array $data = []
    ) {
		$this->_messageHelper = $messageHelper;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_giftMessageSave = $giftMessageSave;
        $this->_taxConfig = $taxConfig;
        $this->_taxData = $taxData;
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
         parent::__construct(
              $context, 
              $sessionQuote, 
              $orderCreate, 
              $priceCurrency, 
              $wishlistFactory, 
              $giftMessageSave, 
              $taxConfig, 
              $taxData, 
              $messageHelper, 
              $stockRegistry, 
              $stockState, 
              $data
    );
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
    
     public function getCreditReasonCode()
    {
        // get the reason code
        $creditReasonCode = $this->getItem()->getData('reason_code');

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
