<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderItemFactory;
use SMG\SubscriptionApi\Model\SubscriptionFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderItemFactory;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class Subscription extends AbstractDb
{

    /** @var \SMG\SubscriptionApi\Model\Subscription */
    protected $_subscription;

    /** @var SubscriptionFactory */
    protected $_subscriptionFactory;

    /** @var SubscriptionOrderFactory  */
    protected $_subscriptionOrderFactory;

    /** @var SubscriptionOrderItemFactory  */
    protected $_subscriptionOrderItemFactory;

    /** @var SubscriptionAddonOrderFactory  */
    protected $_subscriptionAddonOrderFactory;

    /** @var SubscriptionAddonOrderItemFactory  */
    protected $_subscriptionAddonOrderItemFactory;

    /** @var SubscriptionCollectionFactory  */
    protected $_subscriptionCollectionFactory;

    /** @var ProductRepositoryInterface  */
    protected $_productRepository;

    /** @var LoggerInterface  */
    protected $_logger;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(
            'subscription',
            'entity_id'
        );
    }

    /**
     * Subscription constructor.
     * @param Context $context
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionOrderFactory $subscriptionOrderFactory
     * @param SubscriptionOrderItemFactory $subscriptionOrderItemFactory
     * @param SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory
     * @param SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionOrderFactory $subscriptionOrderFactory,
        SubscriptionOrderItemFactory $subscriptionOrderItemFactory,
        SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory,
        SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        RecurlyHelper $recurlyHelper,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionOrderFactory = $subscriptionOrderFactory;
        $this->_subscriptionOrderItemFactory = $subscriptionOrderItemFactory;
        $this->_subscriptionAddonOrderFactory = $subscriptionAddonOrderFactory;
        $this->_subscriptionAddonOrderItemFactory = $subscriptionAddonOrderItemFactory;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_productRepository = $productRepository;
        $this->_logger = $logger;
        $this->_recurlyHelper = $recurlyHelper;
    }

    /**
     * Get the subscription by quiz_id
     * @param string $quizId
     * @return \SMG\SubscriptionApi\Model\Subscription|mixed
     * @throws LocalizedException
     */
    public function getSubscriptionByQuizId(string $quizId)
    {
        if (! empty($quizId)) {
            $subscriptions = $this->_subscriptionCollectionFactory->create();
            $subscription = $subscriptions
                ->addFieldToFilter('quiz_id', $quizId)
                ->getFirstItem();

            if ($subscription && $subscription->getId()) {
                return $subscription;
            }
        }

        $error = "Subscription could not be found with quiz ID '{$quizId}'.";

        throw new LocalizedException(__($error));
    }

    /**
     * Get subscription from master subscription id
     * @param string $masterSubscription
     * @return Subscription|\Magento\Framework\DataObject|null
     * @throws LocalizedException
     */
    public function getSubscriptionByMasterSubscriptionId(string $masterSubscription)
    {
        if (! empty($masterSubscription)) {
            $subscriptions = $this->_subscriptionCollectionFactory->create();
            $subscription = $subscriptions
                ->addFieldToFilter('subscription_id', $masterSubscription)
                ->addFieldToFilter('subscription_status', array('neq' => 'renewed'))
                ->getFirstItem();

            if (! $subscription->getId()) {
                return null;
            }

            return $subscription;
        }

        $error = 'Subscription could not be found with Master Subscription Id.';
        $this->_logger->error($error);

        throw new LocalizedException(__($error));
    }

    /**
     * Create a Subscription from a Recommendation object from Recommendation Engine
     * @param array $recommendation
     * @param string $zip
     * @param mixed $lawnSize
     * @param mixed $lawnType
     * @param mixed $origin
     * @return \SMG\SubscriptionApi\Model\Subscription
     * @throws \Exception
     */
    public function createFromRecommendation($recommendation, $zip, $lawnSize = null, $lawnType = null, $origin = 'web')
    {
        // Create Subscription
        $results = $recommendation[0];

        /** @var DataObject $subscriptionOrder */
        $subscription = $this->_subscriptionFactory->create();
        $subscription->addData([
            'quiz_id' => $results['id'],
            'quiz_completed_at' => $results['completedAt'],
            'lawn_zip' => $zip,
            'lawn_size' => (int) $lawnSize,
            'lawn_type' => $lawnType,
            'zone_name' => $results['plan']['zoneName'],
            'subscription_status' => 'pending',
            'origin' => 'web',
        ])->save();

        $this->_subscription = $subscription;

        // Create Subscription Orders
        $recommendationSubscriptionOrders = $this->organizeSubscriptionOrdersFromRecommendation($results['plan']['coreProducts']);

        $subscriptionPrice = 0;

        foreach ($recommendationSubscriptionOrders as $recommendationSubscriptionOrder) {
            /** @var DataObject $subscriptionOrder */
            $subscriptionOrder = $this->_subscriptionOrderFactory->create();
            $subscriptionOrder->addData([
                'subscription_entity_id' => $subscription->getId(),
                'season_name' => $recommendationSubscriptionOrder['season_name'],
                'season_slug' => $this->getPlanCodeByName($recommendationSubscriptionOrder['season_name']),
                'application_start_date' => $recommendationSubscriptionOrder['application_start_date'],
                'application_end_date' => $recommendationSubscriptionOrder['application_end_date'],
                'subscription_order_status' => $recommendationSubscriptionOrder['subscription_order_status'],
            ])->save();

            $subscriptionOrderPrice = 0;

            // Create the Subscription Order Items
            foreach ($recommendationSubscriptionOrder['subscriptionOrderItems'] as $item) {
                // Get the product.
                $product = $this->_productRepository->get($item['catalog_product_sku']);

                /** @var DataObject $subscriptionOrderItem */
                $subscriptionOrderItem = $this->_subscriptionOrderItemFactory->create();
                $subscriptionOrderItem->addData([
                    'subscription_order_entity_id' => $subscriptionOrder->getId(),
                    'catalog_product_sku' => $item['catalog_product_sku'],
                    'qty' => $item['qty'],
                    'price' => $product->getPrice(),
                ])->save();

                $subscriptionOrderPrice += $product->getPrice() * $item['qty'];
            }

            // Update the price for the subscription.
            $subscriptionPrice += $subscriptionOrderPrice;

            // Set the price for the subscription order.
            $subscriptionOrder->setData('price', $subscriptionOrderPrice)->save();
        }

        // Set Subscription Total Price
        $subscription->setData('price', $subscriptionPrice)->save();

        // Create Subscription Addon Orders
        $recommendationSubscriptionAddonOrder = $this->organizeSubscriptionAddonOrdersFromRecommendation($recommendation[0]['plan']['addOnProducts']);

        if (! empty($recommendationSubscriptionAddonOrder)) {
            /** @var DataObject $subscriptionAddonOrder */
            $subscriptionAddonOrder = $this->_subscriptionAddonOrderFactory->create();
            $subscriptionAddonOrder->addData([
                'subscription_entity_id' => $subscription->getId(),
                'season_name' => $recommendationSubscriptionAddonOrder['season_name'],
                'application_start_date' => $recommendationSubscriptionAddonOrder['application_start_date'],
                'application_end_date' => $recommendationSubscriptionAddonOrder['application_end_date'],
                'subscription_order_status' => $recommendationSubscriptionAddonOrder['subscription_order_status'],
            ])->save();

            $subscriptionAddonOrderPrice = 0;

            // Create the Subscription Order Items
            if (isset($recommendationSubscriptionAddonOrder['subscriptionOrderItems'][0])) {
                $item = $recommendationSubscriptionAddonOrder['subscriptionOrderItems'][0];

                // Get the product.
                $product = $this->_productRepository->get($item['catalog_product_sku']);

                /** @var DataObject $subscriptionOrderItem */
                $subscriptionAddonOrderItem = $this->_subscriptionAddonOrderItemFactory->create();
                $subscriptionAddonOrderItem->addData([
                    'subscription_addon_order_entity_id' => $subscriptionAddonOrder->getId(),
                    'catalog_product_sku' => $item['catalog_product_sku'],
                    'qty' => $item['qty'],
                    'price' => $product->getPrice(),
                ])->save();

                $subscriptionAddonOrderPrice += $product->getPrice() * $item['qty'];
            }

            $subscriptionAddonOrder->setData('price', $subscriptionAddonOrderPrice)->save();
        }

        return $this->_subscription;
    }

    /**
     * Organize the recommended products to be useful
     * @param $recommendedProducts
     * @return array
     * @throws NoSuchEntityException
     */
    private function organizeSubscriptionOrdersFromRecommendation($recommendedProducts)
    {

        // Process the recommendation results
        $subscriptionOrders = [];
        foreach ($recommendedProducts as $recommendedProduct) {

            // Going to want to sort this date time ascending later
            $key = strtotime($recommendedProduct['applicationStartDate']);

            // If there isn't a parent subscription, let's capture that
            if (! isset($subscriptionOrders[$key])) {
                $subscriptionOrders[$key] = [
                    'season_name' => $recommendedProduct['season'],
                    'application_start_date' => $recommendedProduct['applicationStartDate'],
                    'application_end_date' => $recommendedProduct['applicationEndDate'],
                    'subscription_order_status' => 'pending'
                ];
            }

            // If there is a parent subscription order (which there must be now) let's add subscription order items
            if (! isset($seasons[$key]['subscriptionOrderItems'][0])) {
                // Get the corresponding product
                try {
                    $product = $this->_productRepository->get($recommendedProduct['sku']);
                } catch (NoSuchEntityException $e) {
                    throw new NoSuchEntityException(__($e->getMessage() . ' - SKU: ' . $recommendedProduct['sku']));
                }

                $subscriptionOrders[$key]['subscriptionOrderItems'][] = [
                    'catalog_product_sku' => $recommendedProduct['sku'],
                    'qty' => $recommendedProduct['quantity']
                ];
            }
        }

        // Sort by date ascending and return
        ksort($subscriptionOrders);

        return $subscriptionOrders;
    }

    /**
     * Organize the subscription addons from the recommendations payload
     * @param $recommendedProducts
     * @return array
     * @throws NoSuchEntityException
     */
    public function organizeSubscriptionAddonOrdersFromRecommendation($recommendedProducts)
    {
        // We are only concerned with the first season of addons
        $subscriptionAddonOrdersAll = $this->organizeSubscriptionOrdersFromRecommendation($recommendedProducts);

        return array_shift($subscriptionAddonOrdersAll);
    }

    /**
     * Return Recurly Plan Code base on the name of the core product
     *
     * @param $name
     * @return string
     */
    private function getPlanCodeByName($name)
    {
        return $this->_recurlyHelper->getSeasonSlugByName($name);
    }
}
