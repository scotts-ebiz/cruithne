<?php

namespace SMG\SubscriptionApi\Model\ResourceModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use SMG\SubscriptionApi\Model\SubscriptionFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderItemFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;


/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Model\ResourceModel
 */
class Subscription extends AbstractDb
{

    /**
     * @var \SMG\SubscriptionApi\Model\Subscription
     */
    protected $_subscription;

    protected $_subscriptionFactory;
    protected $_subscriptionOrderFactory;
    protected $_subscriptionOrderItemFactory;
    protected $_subscriptionCollectionFactory;
    protected $_subscriptionOrderCollectionFactory;
    protected $_productRepository;
    protected $_subscriptionOrders;

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
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionOrderFactory $subscriptionOrderFactory,
        SubscriptionOrderItemFactory $subscriptionOrderItemFactory,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        ProductRepositoryInterface $productRepository,
        $connectionName = null
    )
    {
        parent::__construct($context, $connectionName);

        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionOrderFactory = $subscriptionOrderFactory;
        $this->_subscriptionOrderItemFactory = $subscriptionOrderItemFactory;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_productRepository = $productRepository;
    }

    /**
     * Get the subscription by quiz_id
     * @param $quizId
     * @return mixed
     */
    public function getSubscriptionByQuizId($quizId)
    {
        $this->_subscription = $this->_subscriptionFactory->create();

        if ( ! empty( $quizId ) )
        {
            // get the list of sapOrders for the provided orderId
            $subscriptions = $this->_subscriptionCollectionFactory->create();
            $subscriptions->addFieldToFilter('quiz_id', $quizId );

            foreach( $subscriptions as $subscription )
            {
                if ( ! empty($subscription) )
                {
                    $this->_subscription = $subscription;
                    break;
                }
            }
        }

        return $this->_subscription;
    }

    /**
     * Get subscription orders
     * @return mixed
     */
    public function getSubscriptionOrders()
    {
        if ( ! isset($this->_subscriptionOrders) ) {
            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders->addFieldToFilter( 'subscription_entity_id', $this->_subscription->getEntityId() );
            $this->_subscriptionOrders = $subscriptionOrders;
        }

        return $this->_subscriptionOrders;
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
    public function createFromRecommendation($recommendation, $zip, $lawnSize = null, $lawnType = null, $origin = 'web') {

        // Create Subscription
        $subscription = $this->_subscriptionFactory->create();
        $subscription->setQuizId( $recommendation[0]['id'] );
        $subscription->setQuizCompletedAt( $recommendation[0]['completedAt'] );
        $subscription->setLawnZip( $zip );
        $subscription->setLawnSize( (int)$lawnSize );
        $subscription->setLawnType( $lawnType );
        $subscription->setZoneName( $recommendation[0]['plan']['zoneName'] );
        $subscription->setOrigin( 'web' );
        $subscription->setSubscriptionStatus( 'pending' );
        $subscription->save();
        $this->_subscription = $subscription;

        // Create Subscription Orders
        $recommendationSubscriptionOrders = $this->organizeSubscriptionOrdersFromRecommendation($recommendation[0]['plan']['coreProducts']);

        foreach ( $recommendationSubscriptionOrders as $recommendationSubscriptionOrder ) {

            $subscriptionOrder = $this->_subscriptionOrderFactory->create();
            $subscriptionOrder->setSubscriptionEntityId( $subscription->getEntityId() );
            $subscriptionOrder->setSeasonName( $recommendationSubscriptionOrder['season_name'] );
            $subscriptionOrder->setApplicationStartDate( $recommendationSubscriptionOrder['application_start_date'] );
            $subscriptionOrder->setApplicationEndDate( $recommendationSubscriptionOrder['application_end_date'] );
            $subscriptionOrder->setSubscriptionOrderStatus( $recommendationSubscriptionOrder['subscription_order_status'] );
            $subscriptionOrder->save();

            // Create the Subscription Order Items
            foreach ( $recommendationSubscriptionOrder['subscriptionOrderItems'] as $item ) {
                $subscriptionOrderItem = $this->_subscriptionOrderItemFactory->create();
                $subscriptionOrderItem->setSubscriptionOrderEntityId( $subscriptionOrder->getEntityId() );
                $subscriptionOrderItem->setCatalogProductSku( $item['catalog_product_sku'] );
                $subscriptionOrderItem->setQty( $item['qty'] );
                $subscriptionOrderItem->save();
            }
        }

        return $this->_subscription;
    }

    /**
     * Organize the recommended products to be useful
     * @param $recommendedProducts
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function organizeSubscriptionOrdersFromRecommendation($recommendedProducts) {

        // Process the recommendation results
        $seasons = [];
        foreach ( $recommendedProducts as $recommendedProduct ) {

            // Going to want to sort this date time ascending later
            $key = strtotime( $recommendedProduct['applicationStartDate'] );

            // If there isn't a parent subscription, let's capture that
            if ( ! isset( $seasons[$key] ) ) {

                $seasons[$key] = [
                    'season_name' => $recommendedProduct['season'],
                    'application_start_date' => $recommendedProduct['applicationStartDate'],
                    'application_end_date' => $recommendedProduct['applicationEndDate'],
                    'subscription_order_status' => 'pending'
                ];
            }

            // If there is a parent subscription order (which there must be now) let's add subscription order items
            if ( ! isset( $seasons[$key]['subscriptionOrderItems'][0] ) ) {

                // Get the corresponding product
                $product = $this->_productRepository->get($recommendedProduct['sku']);

                // @todo Error state with sku mismatch... what to do?
                if ( ! $product->getEntityId()) {

                }

                $seasons[$key]['subscriptionOrderItems'][] = [
                    'catalog_product_sku' => $recommendedProduct['sku'],
                    'qty' => $recommendedProduct['quantity']
                ];
            }

        }

        // Sort by date ascending and return
        ksort($seasons);

        return $seasons;
    }
}