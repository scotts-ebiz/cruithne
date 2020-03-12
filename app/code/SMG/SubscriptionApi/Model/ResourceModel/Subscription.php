<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use SMG\SubscriptionApi\Helper\RecurlyHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Psr\Log\LoggerInterface;
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

        $error = 'Subscription could not be found with quiz id.';

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
        $subscription = $this->_subscriptionFactory->create();
        $subscription->setQuizId($recommendation[0]['id']);
        $subscription->setQuizCompletedAt($recommendation[0]['completedAt']);
        $subscription->setLawnZip($zip);
        $subscription->setLawnSize((int)$lawnSize);
        $subscription->setLawnType($lawnType);
        $subscription->setZoneName($recommendation[0]['plan']['zoneName']);
        $subscription->setOrigin('web');
        $subscription->setSubscriptionStatus('pending');
        $subscription->save();

        $this->_subscription = $subscription;

        // Create Subscription Orders
        $recommendationSubscriptionOrders = $this->organizeSubscriptionOrdersFromRecommendation($recommendation[0]['plan']['coreProducts']);

        $subscriptionPrice = 0;

        foreach ($recommendationSubscriptionOrders as $recommendationSubscriptionOrder) {
            $subscriptionOrder = $this->_subscriptionOrderFactory->create();
            $subscriptionOrder->setSubscriptionEntityId($subscription->getEntityId());
            $subscriptionOrder->setSeasonName($recommendationSubscriptionOrder['season_name']);
            $subscriptionOrder->setSeasonSlug($this->getPlanCodeByName($recommendationSubscriptionOrder['season_name']));
            $subscriptionOrder->setApplicationStartDate($recommendationSubscriptionOrder['application_start_date']);
            $subscriptionOrder->setApplicationEndDate($recommendationSubscriptionOrder['application_end_date']);
            $subscriptionOrder->setSubscriptionOrderStatus($recommendationSubscriptionOrder['subscription_order_status']);
            $subscriptionOrder->save();

            $subscriptionOrderPrice = 0;

            // Create the Subscription Order Items
            foreach ($recommendationSubscriptionOrder['subscriptionOrderItems'] as $item) {
                $subscriptionOrderItem = $this->_subscriptionOrderItemFactory->create();
                $subscriptionOrderItem->setSubscriptionOrderEntityId($subscriptionOrder->getEntityId());
                $subscriptionOrderItem->setCatalogProductSku($item['catalog_product_sku']);
                $subscriptionOrderItem->setQty($item['qty']);
                $product = $this->_productRepository->get($item['catalog_product_sku']);
                $subscriptionOrderItem->setPrice($product->getPrice());
                $subscriptionOrderItem->save();
                $subscriptionOrderPrice += $product->getPrice() * $item['qty'];
            }

            $subscriptionPrice += $subscriptionOrderPrice;
            $subscriptionOrder->setApplicationStartDate($recommendationSubscriptionOrder['application_start_date']);
            $subscriptionOrder->setApplicationEndDate($recommendationSubscriptionOrder['application_end_date']);
            $subscriptionOrder->setPrice($subscriptionOrderPrice);
            $subscriptionOrder->save();
        }

        // Set Subscription Total Price
        $subscription->setPrice($subscriptionPrice);
        $subscription->save();

        // Create Subscription Addon Orders
        $recommendationSubscriptionAddonOrder = $this->organizeSubscriptionAddonOrdersFromRecommendation($recommendation[0]['plan']['addOnProducts']);

        if (! empty($recommendationSubscriptionAddonOrder)) {
            $subscriptionAddonOrder = $this->_subscriptionAddonOrderFactory->create();
            $subscriptionAddonOrder->setSubscriptionEntityId($subscription->getEntityId());
            $subscriptionAddonOrder->setSeasonName($recommendationSubscriptionAddonOrder['season_name']);
            $subscriptionAddonOrder->setApplicationStartDate($recommendationSubscriptionAddonOrder['application_start_date']);
            $subscriptionAddonOrder->setApplicationEndDate($recommendationSubscriptionAddonOrder['application_end_date']);
            $subscriptionAddonOrder->setSubscriptionOrderStatus($recommendationSubscriptionAddonOrder['subscription_order_status']);
            $subscriptionAddonOrder->save();

            $subscriptionAddonOrderPrice = 0;

            // Create the Subscription Order Items
            if (isset($recommendationSubscriptionAddonOrder['subscriptionOrderItems'][0])) {
                $item = $recommendationSubscriptionAddonOrder['subscriptionOrderItems'][0];
                $subscriptionAddonOrderItem = $this->_subscriptionAddonOrderItemFactory->create();
                $subscriptionAddonOrderItem->setSubscriptionAddonOrderEntityId($subscriptionAddonOrder->getEntityId());
                $subscriptionAddonOrderItem->setCatalogProductSku($item['catalog_product_sku']);
                $subscriptionAddonOrderItem->setQty($item['qty']);
                $product = $this->_productRepository->get($item['catalog_product_sku']);
                $subscriptionAddonOrderItem->setPrice($product->getPrice());
                $subscriptionAddonOrderItem->save();
                $subscriptionAddonOrderPrice += $product->getPrice() * $item['qty'];
            }

            $subscriptionAddonOrder->setPrice($subscriptionAddonOrderPrice);
            $subscriptionAddonOrder->save();
        }

        return $this->_subscription;
    }

    /**
     * Organize the recommended products to be useful
     * @param $recommendedProducts
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
