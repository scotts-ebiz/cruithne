<?php

namespace SMG\Sales\Block\Adminhtml\Order\View;

use SMG\Sales\Helper\AccountDetailsHelper;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{
	/**
     * Customer service
     *
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $metadata;

    /**
     * Group service
     *
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Metadata element factory
     *
     * @var \Magento\Customer\Model\Metadata\ElementFactory
     */
    protected $_metadataElementFactory;

    /**
     * @var Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @var AccountDetailsHelper
     */
    protected $_accountDetailHelper;

	public function __construct(
	    \Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
		 AccountDetailsHelper $accountDetailsHelper,
		 array $data = [])
    {
        $this->groupRepository = $groupRepository;
        $this->metadata = $metadata;
        $this->_metadataElementFactory = $elementFactory;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $registry, $adminHelper, $groupRepository, $metadata, $elementFactory, $addressRenderer, $data);

        $this->_accountDetailHelper = $accountDetailsHelper;
	}
	
	public function getOrderInfo()
    {
        // get the order id
        $orderId = $this->getOrder()->getId();

        // return the array of values for display
        return $this->_accountDetailHelper->getOrderInfo($orderId);
    }
}
