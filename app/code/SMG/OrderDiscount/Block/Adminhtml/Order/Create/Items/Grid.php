<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\OrderDiscount\Block\Adminhtml\Order\Create\Items;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use SMG\OrderDiscount\Model\OrderCustomDiscountFactory;
use SMG\OrderDiscount\Model\ResourceModel\OrderCustomDiscount as OrderCustomDiscountResource;
use SMG\OrderDiscount\Model\ResourceModel\OrderCustomDiscount\CollectionFactory as OrderCustomDiscountCollectionFactory;
/**
 * Adminhtml sales order create items grid block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Grid extends \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid
{
    /**
     * @var _orderCustomDiscountFactory
     */
    protected $_orderCustomDiscountFactory;

    /**
     * @var _orderCustomDiscountResource
     */
    protected $_orderCustomDiscountResource;

    /**
     * @var _orderCustomDiscountCollectionFactory
     */
    protected $_orderCustomDiscountCollectionFactory;
    
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
        OrderCustomDiscountFactory $orderCustomDiscountFactory,
        OrderCustomDiscountResource $_orderCustomDiscountResource,
        OrderCustomDiscountCollectionFactory $orderCustomDiscountCollectionFactory,
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
        $this->_orderCustomDiscountFactory = $orderCustomDiscountFactory;
        $this->_orderCustomDiscountResource = $_orderCustomDiscountResource;
        $this->_orderCustomDiscountCollectionFactory = $orderCustomDiscountCollectionFactory;
       
    }
    
    public function getOrderCustomDiscount()
    {
        // Get the list of active reason codes
        $orderCustomDiscount = $this->_orderCustomDiscountCollectionFactory->create();

        // return the reason codes
        return $orderCustomDiscount;
    }
}
