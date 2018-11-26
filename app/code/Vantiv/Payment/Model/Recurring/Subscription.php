<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring;

use Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus;

/**
 * Recurring Plan
 *
 * @method int getStoreId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setStoreId(int $value)
 * @method string getCreatedAt()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setUpdatedAt(string $value)
 * @method int getPlanId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setPlanId(int $value)
 * @method float getIntervalAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setIntervalAmount(float $value)
 * @method string getStartDate()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setStartDate(string $value)
 * @method int getVantivSubscriptionId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setVantivSubscriptionId(int $value)
 * @method int getCustomerId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setCustomerId(int $value)
 * @method int getOriginalOrderId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setOriginalOrderId(int $value)
 * @method string getOriginalOrderIncrementId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setOriginalOrderIncrementId(string $value)
 * @method int getProductId()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setProductId(int $value)
 * @method string getProductName()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setProductName(string $value)
 * @method string getBillingName()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setBillingName(string $value)
 * @method string getShippingName()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setShippingName(string $value)
 * @method string getStatus()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setStatus(string $value)
 * @method float getDiscountAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setDiscountAmount(float $value)
 * @method string getDiscountDescription()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setDiscountDescription(string $value)
 * @method float getShippingAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setShippingAmount(float $value)
 * @method float getShippingTaxAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setShippingTaxAmount(float $value)
 * @method float getSubtotal()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setSubtotal(float $value)
 * @method float getTaxAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setTaxAmount(float $value)
 * @method float getSubtotalInclTax()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setSubtotalInclTax(float $value)
 * @method float getItemPrice()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setItemPrice(float $value)
 * @method float getItemOriginalPrice()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setItemOriginalPrice(float $value)
 * @method float getItemTaxPercent()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setItemTaxPercent(float $value)
 * @method float getItemTaxAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setItemTaxAmount(float $value)
 * @method float getItemDiscountPercent()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setItemDiscountPercent(float $value)
 * @method float getItemDiscountAmount()
 * @method \Vantiv\Payment\Model\Recurring\Subscription setItemDiscountAmount(float $value)
 */
class Subscription extends \Magento\Framework\Model\AbstractModel
{
    const REGISTRY_NAME = 'current_vantiv_subscription';

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus
     */
    private $statusSource;

    /**
     * @var array
     */
    private $statuses;

    /**
     * @var \Vantiv\Payment\Gateway\Recurring\UpdateSubscriptionCommand
     */
    private $updateSubscriptionCommand;

    /**
     * @var \Vantiv\Payment\Gateway\Recurring\CancelSubscriptionCommand
     */
    private $cancelSubscriptionCommand;

    /**
     * @var \Vantiv\Payment\Model\Recurring\PlanFactory
     */
    private $planFactory;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Plan
     */
    private $plan;

    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Address\CollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var \Magento\Sales\Model\Order|bool
     */
    private $lastOrder;
    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon\CollectionFactory
     */
    private $addonCollectionFactory;
    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount\CollectionFactory
     */
    private $discountCollectionFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Addresses array
     *
     * @var array
     */
    private $addresses;

    /**
     * Addons array
     *
     * @var array
     */
    private $addons;

    /**
     * Discounts array
     *
     * @var array
     */
    private $discounts;

    /**
     * Local cache for total amount to date
     *
     * @var array
     */
    private $totalAmountToDate = [];

    /**
     * Subscription model constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param SubscriptionStatus $statusSource
     * @param \Vantiv\Payment\Gateway\Recurring\UpdateSubscriptionCommand $updateSubscriptionCommand
     * @param \Vantiv\Payment\Gateway\Recurring\CancelSubscriptionCommand $cancelSubscriptionCommand
     * @param \Vantiv\Payment\Model\Recurring\PlanFactory $planFactory
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Address\CollectionFactory $addressCollectionFactory
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon\CollectionFactory $addonCollectionFactory
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount\CollectionFactory $discountCollectionFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SubscriptionStatus $statusSource,
        \Vantiv\Payment\Gateway\Recurring\UpdateSubscriptionCommand $updateSubscriptionCommand,
        \Vantiv\Payment\Gateway\Recurring\CancelSubscriptionCommand $cancelSubscriptionCommand,
        \Vantiv\Payment\Model\Recurring\PlanFactory $planFactory,
        \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Address\CollectionFactory $addressCollectionFactory,
        \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon\CollectionFactory $addonCollectionFactory,
        \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount\CollectionFactory $discountCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeManager = $storeManager;
        $this->statusSource = $statusSource;
        $this->updateSubscriptionCommand = $updateSubscriptionCommand;
        $this->cancelSubscriptionCommand = $cancelSubscriptionCommand;
        $this->planFactory = $planFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->addonCollectionFactory = $addonCollectionFactory;
        $this->discountCollectionFactory = $discountCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vantiv\Payment\Model\ResourceModel\Recurring\Subscription');
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        if (!$this->getSkipSendingToVantiv() && $this->getId()) {
            if (!$this->getOrigData()
                || $this->getOrigData('status') != $this->getData('status')
                && $this->getStatus() == SubscriptionStatus::CANCELLED
            ) {
                $this->cancelSubscriptionCommand->execute(['subscription' => $this]);
            } else {
                $this->updateSubscriptionCommand->execute(['subscription' => $this]);
            }
        }

        return parent::beforeSave();
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        if ($this->isObjectNew() || ($this->getOrigData() && $this->dataHasChangedFor('plan_id'))) {
            $this->addAmountChangelog([
                'entity_id' => $this->getId(),
                'entity_type' => 'plan',
                'amount' => $this->getPlan()->getIntervalAmount()
            ]);
        }

        if ($this->getAddresses() !== null && is_array($this->getAddresses())) {
            foreach ($this->getAddresses() as $address) {
                $address->setSubscriptionId($this->getId())
                    ->save();
            }
        }

        if ($this->getAddonList() !== null && is_array($this->getAddonList())) {
            foreach ($this->getAddonList() as $addon) {
                $addon->setData('skip_sending_to_vantiv', true)
                    ->setSubscriptionId($this->getId())
                    ->save();
            }
        }

        if ($this->getDiscountList() !== null && is_array($this->getDiscountList())) {
            foreach ($this->getDiscountList() as $discount) {
                $discount->setData('skip_sending_to_vantiv', true)
                    ->setSubscriptionId($this->getId())
                    ->save();
            }
        }

        return parent::afterSave();
    }

    /**
     * Retrieve associated array of all subscription statuses and their labels
     *
     * @return array
     */
    private function getStatuses()
    {
        if ($this->statuses === null) {
            $this->statuses = $this->statusSource->toOptionHash();
        }

        return $this->statuses;
    }

    /**
     * Format amount
     *
     * @param float $amount
     * @param int|null $storeId
     * @return string
     */
    private function formatAmount($amount, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getStoreId();
        }
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        return $this->storeManager->getWebsite($websiteId)->getBaseCurrency()->formatPrecision(
            $amount,
            2
        );
    }

    /**
     * Get formatted interval amount
     *
     * @return string
     */
    public function getFormattedIntervalAmount()
    {
        return $this->formatAmount($this->getIntervalAmount());
    }

    /**
     * Get formatted interval amount
     *
     * @return string
     */
    public function getFormattedPlanIntervalAmount()
    {
        $amount = $this->getPlan() ? $this->getPlan()->getIntervalAmount() : 0;
        return $this->formatAmount($amount);
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $statuses = $this->getStatuses();
        return isset($statuses[$this->getStatus()]) ? $statuses[$this->getStatus()] : '';
    }

    /**
     * Retrieve store model instance
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            return $this->storeManager->getStore($storeId);
        }
        return $this->storeManager->getStore();
    }

    /**
     * @return Plan
     */
    public function getPlan()
    {
        if ($this->plan === null) {
            $plan = false;
            if ($this->getPlanId()) {
                $plan = $this->planFactory->create()->load($this->getPlanId());
                if (!$plan->getId()) {
                    $plan = false;
                }
            }
            $this->plan = $plan;
        }

        return $this->plan;
    }

    /**
     * @param Plan $plan
     * @return $this
     */
    public function setPlan(Plan $plan)
    {
        $this->plan = $plan;
        return $this;
    }

    /**
     * Update subscription id in original order
     *
     * @return $this
     */
    public function updateOriginalOrderRelation()
    {
        if (!($this->getOriginalOrderId() && $this->getId())) {
            return $this;
        }

        $this->getResource()->updateOrderRelation($this->getOriginalOrderId(), $this->getId());
        return $this;
    }

    /**
     * @return \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Address\Collection
     */
    public function getAddressesCollection()
    {
        $collection = $this->addressCollectionFactory->create()->setSubscriptionFilter($this);
        if ($this->getId()) {
            foreach ($collection as $address) {
                $address->setSubscription($this);
            }
        }
        return $collection;
    }

    /**
     * @return \Vantiv\Payment\Model\Recurring\Subscription\Address[]
     */
    public function getAddresses()
    {
        if ($this->addresses === null) {
            $this->addresses = $this->getAddressesCollection()->getItems();
        }
        return $this->addresses;
    }

    /**
     * @param array $addresses
     * @return $this
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
        return $this;
    }

    /**
     * Retrieve order billing address
     *
     * @return \Vantiv\Payment\Model\Recurring\Subscription\Address|null
     */
    public function getBillingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getAddressType() == 'billing' && !$address->isDeleted()) {
                return $address;
            }
        }
        return null;
    }

    /**
     * Retrieve order shipping address
     *
     * @return \Magento\Sales\Model\Order\Address|null
     */
    public function getShippingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getAddressType() == 'shipping' && !$address->isDeleted()) {
                return $address;
            }
        }
        return null;
    }

    /**
     * @return \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon\Collection
     */
    public function getAddonCollection()
    {
        $collection = $this->addonCollectionFactory->create()->setSubscriptionFilter($this);
        if ($this->getId()) {
            /** @var Subscription\Addon $addon */
            foreach ($collection as $addon) {
                $addon->setSubscription($this);
            }
        }

        return $collection;
    }

    /**
     * @return Subscription\Addon[]
     */
    public function getAddonList()
    {
        if ($this->addons === null) {
            $this->addons = $this->getAddonCollection()->getItems();
        }

        return $this->addons;
    }

    /**
     * @param array $addonList
     * @return $this
     */
    public function setAddonList(array $addonList)
    {
        $this->addons = $addonList;
        return $this;
    }

    /**
     * @param Subscription\Addon $addon
     */
    public function addAddon(Subscription\Addon $addon)
    {
        $addon->setSubscription($this);
        $addonList = $this->getAddonList();
        $addonList[] = $addon;

        $this->setAddonList($addonList);
    }

    /**
     * @return \Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount\Collection
     */
    public function getDiscountCollection()
    {
        $collection = $this->discountCollectionFactory->create()->setSubscriptionFilter($this);
        if ($this->getId()) {
            /** @var Subscription\Discount $discount */
            foreach ($collection as $discount) {
                $discount->setSubscription($this);
            }
        }

        return $collection;
    }

    /**
     * @return Subscription\Discount[]
     */
    public function getDiscountList()
    {
        if ($this->discounts === null) {
            $this->discounts = $this->getDiscountCollection()->getItems();
        }

        return $this->discounts;
    }

    /**
     * @param array $discountList
     * @return $this
     */
    public function setDiscountList(array $discountList)
    {
        $this->discounts = $discountList;
        return $this;
    }

    /**
     * @param Subscription\Discount $discount
     */
    public function addDiscount(Subscription\Discount $discount)
    {
        $discount->setSubscription($this);
        $discountList = $this->getDiscountList();
        $discountList[] = $discount;

        $this->setDiscountList($discountList);
    }

    /**
     * Retrieve last order associated with the subscription
     *
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getLastOrder()
    {
        if ($this->lastOrder === null) {
            $this->lastOrder = false;
            if ($this->getId()) {
                $sortOrder = $this->sortOrderBuilder->setField('created_at')->setDescendingDirection()->create();
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('vantiv_subscription_id', $this->getId())
                    ->addSortOrder($sortOrder)
                    ->setPageSize(1)
                    ->setCurrentPage(1)
                    ->create();
                $orders = $this->orderRepository->getList($searchCriteria)->getItems();
                if ($orders && ($order = array_pop($orders)) && $order->getId()) {
                    $this->lastOrder = $this->orderRepository->get($order->getId());
                }
            }
        }

        return $this->lastOrder;
    }

    /**
     * Get product options array
     *
     * @return array
     */
    public function getProductOptions()
    {
        $data = $this->_getData('product_options');
        return is_string($data) ? unserialize($data) : $data;
    }

    /**
     * Change subscription plan, update all related fields
     *
     * @param int $newPlanId
     * @param bool $allowInactive
     * @return $this
     */
    public function changePlan($newPlanId, $allowInactive = false)
    {
        if ($newPlanId == $this->getPlanId()) {
            return $this;
        }

        $newPlan = $this->planFactory->create()->load($newPlanId);
        if (!$newPlan->getId()) {
            throw new \InvalidArgumentException('Plan doesn\'t exist.');
        }

        if ($newPlan->getProductId() != $this->getProductId()) {
            throw new \InvalidArgumentException('Plan doesn\'t belong to subscription product.');
        }

        if (!$allowInactive && !$newPlan->getActive()) {
            throw new \InvalidArgumentException('Plan is inactive.');
        }

        $this->setPlanId($newPlan->getId());
        $this->setPlanCode($newPlan->getCode());

        // calculate subscription amounts based on new plan
        if ($this->getIntervalAmount() !== null) {
            $originalPriceIncrease = $this->getItemOriginalPrice()
            && (($this->getItemPrice() - $this->getItemOriginalPrice()) > 0.0001)
                ? ($this->getItemPrice() - $this->getItemOriginalPrice()) : 0;
            $this->setItemPrice($newPlan->getIntervalAmount() + $originalPriceIncrease)
                ->setItemOriginalPrice($newPlan->getIntervalAmount());
            if ($this->getItemTaxPercent()) {
                $taxExtra = $this->getTaxAmount() - $this->getItemTaxAmount();
                $this->setItemTaxAmount(
                    $this->priceCurrency->round($this->getItemPrice() * $this->getItemTaxPercent() / 100)
                );
                $this->setTaxAmount($this->getItemTaxAmount() + $taxExtra);
            }
            $this->setSubtotal($this->getItemPrice())
                ->setSubtotalInclTax($this->getItemPrice() + $this->getItemTaxAmount());

            $addonsAmount = 0;
            foreach ($this->getAddonList() as $addon) {
                switch ($addon->getCode()) {
                    case Subscription\Addon::TAX_CODE:
                        $addon->setAmount($this->getItemTaxAmount());
                        break;
                }
                if ($addon->getIsSystem()) {
                    $addonsAmount += $addon->getAmount();
                }
            }

            $this->setIntervalAmount($newPlan->getIntervalAmount() + $addonsAmount);
        } else {
            // interval_amount === null means that recurring order total should be equal to plan amount,
            // i.e. there is nothing extra (tax, shipping, etc.) and thus easier calculation
            $this->setItemPrice($newPlan->getIntervalAmount())
                ->setItemOriginalPrice($this->getItemPrice())
                ->setSubtotal($this->getItemPrice())
                ->setSubtotalInclTax($this->getItemPrice());
        }

        // delete all discounts
        $this->setItemDiscountAmount(0)
            ->setItemDiscountPercent(0)
            ->setDiscountAmount(0)
            ->setDiscountDescription('');
        foreach ($this->getDiscountList() as $discount) {
            $discount->isDeleted(true);
        }

        return $this;
    }

    /**
     * Create amount changelog record
     *
     * @param array $changeLogData
     * @return $this
     */
    public function addAmountChangelog($changeLogData)
    {
        if (!isset($changeLogData['subscription_id']) && $this->getId()) {
            $changeLogData['subscription_id'] = $this->getId();
        }
        $this->getResource()->addAmountChangelog($changeLogData);

        return $this;
    }

    /**
     * Retrieve subscription amount at given time
     *
     * @param \DateTime $dateTime
     * @return float|null
     */
    public function getTotalAmountToDate(\DateTime $dateTime)
    {
        $cacheKey = $dateTime->getTimestamp();
        if (!isset($this->totalAmountToDate[$cacheKey])) {
            $this->totalAmountToDate[$cacheKey] = $this->getResource()->getTotalAmountToDate($this, $dateTime);
        }
        return $this->totalAmountToDate[$cacheKey];
    }

    /**
     * Recalculate subscription amounts for date
     *
     * @param \DateTime $dateTime
     * @return $this
     */
    public function recalculateAmountsToDate(\DateTime $dateTime)
    {
        $startDate = $this->getStartDate() ? $this->getStartDate() : $this->getCreatedAt();
        $startDate = new \DateTime($startDate);
        if ($startDate->getTimestamp() > $dateTime->getTimestamp()) {
            return $this;
        }

        $currentTotal = $this->getTotalAmountToDate($dateTime);
        if ($currentTotal === null) {
            return $this;
        }

        if ($currentTotal < 0) {
            $currentTotal = 0;
        }

        $originalTotal = $this->getIntervalAmount() !== null
            ? $this->getIntervalAmount() : $this->getPlan()->getIntervalAmount();
        $totalDiff = $currentTotal - $originalTotal;
        if (abs($totalDiff) < 0.0001) {
            return $this;
        }

        if ($totalDiff < 0) {
            // increase discount
            $this->setDiscountAmount($this->getDiscountAmount() + $totalDiff);
            $this->setItemDiscountAmount($this->getItemDiscountAmount() - $totalDiff);
            $discountDescription = (string)__('Subscription Discounts');
            $this->setDiscountDescription(
                $this->getDiscountDescription()
                    ? $this->getDiscountDescription() . ', ' . $discountDescription : $discountDescription
            );
        } else {
            // increase item price and tax
            $itemTaxDiff = 0;
            if ($this->getItemTaxPercent()) {
                $itemTaxDiff = $this->priceCurrency->round(
                    $totalDiff * (1 - 1 / (1 + $this->getItemTaxPercent() / 100))
                );
                $this->setItemTaxAmount($this->getItemTaxAmount() + $itemTaxDiff);
                $this->setTaxAmount($this->getTaxAmount() + $itemTaxDiff);
                $this->setItemPrice($this->getItemPrice() + $totalDiff - $itemTaxDiff);
            } else {
                $this->setItemPrice($this->getItemPrice() + $totalDiff);
            }
            $this->setSubtotal($this->getSubtotal() + $totalDiff - $itemTaxDiff);
            $this->setSubtotalInclTax($this->getSubtotalInclTax() + $totalDiff);
        }
        $this->setIntervalAmount($currentTotal);

        return $this;
    }
}
