<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Adminhtml customer orders grid block
 */
class Subscriptions extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var array
     */
    private $statuses;

    /**
     * @var array
     */
    private $intervals;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var string
     */
    private $parentTemplate;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\CollectionFactory $collectionFactory
     * @param \Vantiv\Payment\Model\Recurring\Source\Interval $intervalSource
     * @param \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus $statusSource
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\CollectionFactory $collectionFactory,
        \Vantiv\Payment\Model\Recurring\Source\Interval $intervalSource,
        \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus $statusSource,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->intervals = $intervalSource->toOptionHash();
        $this->statuses = $statusSource->toOptionHash();
        $this->localeFormat = $localeFormat;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_subscriptions_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setUseAjax(true);
    }

    /**
     * Apply various selection filters to prepare the sales order grid collection.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create()
            ->addCustomerIdFilter($this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID))
            ->joinPlans(['interval', 'number_of_trial_intervals', 'trial_interval']);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'vantiv_subscription_id',
            [
                'header' => __('Vantiv ID'),
                'index' => 'vantiv_subscription_id',
                'header_css_class' => 'col-vantiv-subscription-id',
                'column_css_class' => 'col-vantiv-subscription-id'
            ]
        );

        $this->addColumn(
            'original_order_increment_id',
            [
                'header' => __('Original Order #'),
                'index' => 'original_order_increment_id',
                'header_css_class' => 'col-original-order-id',
                'column_css_class' => 'col-original-order-id',
                'renderer' => 'Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer\OrderIncrementId'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Purchased'),
                'type' => 'datetime',
                'index' => 'created_at',
                'header_css_class' => 'col-created-at',
                'column_css_class' => 'col-created-at'
            ]
        );

        $this->addColumn(
            'product_name',
            [
                'header' => __('Product'),
                'index' => 'product_name',
                'header_css_class' => 'col-product-name',
                'column_css_class' => 'col-product-name',
                'renderer' => 'Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer\ProductName'
            ]
        );

        $this->addColumn(
            'interval_amount',
            [
                'header' => __('Amount'),
                'index' => 'interval_amount',
                'filter_type' => 'range',
                'header_css_class' => 'col-interval-amount',
                'column_css_class' => 'col-interval-amount',
                'renderer' => 'Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer\IntervalAmount'
            ]
        );

        $this->addColumn(
            'interval',
            [
                'header' => __('Interval'),
                'index' => 'interval',
                'sortable' => false,
                'type' => 'options',
                'options' => $this->intervals,
                'header_css_class' => 'col-interval',
                'column_css_class' => 'col-interval'
            ]
        );

        $this->addColumn(
            'trial_interval',
            [
                'header' => __('Trial'),
                'index' => 'trial_interval',
                'sortable' => false,
                'filter' => false,
                'header_css_class' => 'col-trial-interval',
                'column_css_class' => 'col-trial-interval',
                'renderer' => 'Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer\Trial'
            ]
        );

        $this->addColumn(
            'start_date',
            [
                'header' => __('Start Date'),
                'index' => 'start_date',
                'timezone' => false,
                'type' => 'date',
                'header_css_class' => 'col-start-date',
                'column_css_class' => 'col-start-date'
            ]
        );

        $this->addColumn(
            'next_payment_date',
            [
                'header' => __('Estimated Next Payment Date'),
                'sortable' => false,
                'filter' => false,
                'timezone' => false,
                'type' => 'date',
                'header_css_class' => 'col-next-date',
                'column_css_class' => 'col-next-date',
                'renderer' => 'Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer\NextPaymentDate'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'sortable' => false,
                'type' => 'options',
                'options' => $this->statuses,
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                [
                    'header' => __('Purchase Point'),
                    'index' => 'store_id',
                    'type' => 'store',
                    'store_view' => true
                ]
            );
        }

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'index' => 'action',
                'filter' => false,
                'sortable' => false,
                'type' => 'action',
                'renderer' => 'Magento\Customer\Block\Adminhtml\Grid\Renderer\Multiaction',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => 'vantiv/recurring_subscription/edit',
                            'params' => [
                                'customer_id' => $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)
                            ],
                        ],
                        'field' => 'subscription_id',
                    ],
                    [
                        'caption' => __('Cancel'),
                        'url' => [
                            'base' => 'vantiv/recurring_subscription/cancel',
                            'params' => [
                                'customer_id' => $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)
                            ],
                        ],
                        'confirm' => __('Do you really want to cancel the subscription?'),
                        'field' => 'subscription_id',
                    ],
                ]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('vantiv/recurring_customer/subscriptions', ['_current' => true]);
    }

    /**
     * @inheritdoc
     */
    public function shouldRenderCell($item, $column)
    {
        if ($column->getId() == 'action' &&
            $item->getStatus() == \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::CANCELLED
        ) {
            return false;
        }

        return parent::shouldRenderCell($item, $column);
    }

    /*
     * @inheritdoc
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'vantiv/recurring_subscription/view',
            [
                'subscription_id' => $row->getId(),
                'referrer' => 'customer'
            ]
        );
    }
}
