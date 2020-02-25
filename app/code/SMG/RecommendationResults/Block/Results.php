<?php
namespace SMG\RecommendationResults\Block;

use Magento\Framework\Session\SessionManagerInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\Collection as SubscriptionCollection;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem\CollectionFactory as SubscriptionOrderItemCollectionFactory;

class Results extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper
     */
    protected $_helper;
    
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
    
    
    private $_logger;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

     /**
     * @var Subscription
     */
    protected $_subscription;
    
    /** @var SubscriptionOrderCollectionFactory */
    protected $_subscriptionOrderCollectionFactory;
    
    /** @var SubscriptionOrderItemCollectionFactory */
    protected $_subscriptionOrderItemCollectionFactory;
    
    /** @var productRepository */
    protected $_productRepository;
    
    /** @var subscriptionOrderItems */
    protected $_subscriptionOrderItems;

    /**
     * Quiz constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper $helper,
        array $data = [],
        SessionManagerInterface $coreSession,
        \Psr\Log\LoggerInterface $logger,
        Subscription $subscription,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
        $this->_coreSession = $coreSession;
        $this->_logger = $logger;
        $this->_subscription = $subscription;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionOrderItemCollectionFactory = $subscriptionOrderItemCollectionFactory;
        $this->_productRepository = $productRepository;
    }
    
    /**
     * @return string
     */
    public function getQuizId(){
         $this->_coreSession->start();
        return $this->_coreSession->getQuizId();
    }
    
    /**
     * @return string
     */
    public function getZipCode(){
         $this->_coreSession->start();
        return $this->_coreSession->getZipCode();
    }

    /**
     * @return string
     */
    public function getSubscriptionDetails(){
        $quizid = (string) $this->getQuizId();
        $subscription = $this->_subscription->getSubscriptionByQuizId($quizid);
        return $subscription->getEntityId();
    }

    public function getNewId(){
         $timestamp = strtotime(date("Y-m-d H:i:s"));
         return $this->_coreSession->getTimeStamp().'-'.$timestamp;
    }  
    
    public function getApplicationdetails(){
         $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders
                ->setOrder('ship_start_date', 'asc')
                ->addFieldToFilter('subscription_entity_id', $this->getSubscriptionDetails());
         $this->_subscriptionOrders = $subscriptionOrders;
         $order = array();
         $product_sort = 0;
         foreach($this->_subscriptionOrders as $orders){
             $order['id'][] = $orders->getEntityId();
             $order['startdate'][] = $orders->getApplicationStartDate();
             $order['enddate'][] = $orders->getApplicationEndDate();
             $orderItemId = $this->getOrderItems($orders->getEntityId());
              foreach ($orderItemId as $item) {
                 $product = $this->getProductBySku($item->getCatalogProductSku());
                 $order['product_id'][] = $product->getEntityId();
             }
             $order['product_sort'][] = $product_sort;
             $product_sort++;
         }
         $orderApplication = array();
         $orderApplication['startdate']=strtotime(current($order['startdate']));
         $orderApplication['enddate']=strtotime(end($order['enddate']));
         $orderApplication['product_sort']= implode(",",$order['product_sort']);
         $orderApplication['product_id']= implode(",",$order['product_id']);
         return $orderApplication;
    } 

    public function getOrderItems($entity_id)
    {

        // Make sure we have an actual subscription
        if (empty($entity_id)) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        
        $subscriptionOrderItems = $this->_subscriptionOrderItemCollectionFactory->create();
        $subscriptionOrderItems->addFieldToFilter('subscription_order_entity_id', $entity_id);
        $this->_subscriptionOrderItems = $subscriptionOrderItems;

        return $this->_subscriptionOrderItems;
    }

    public function getProductBySku($sku) {
        return $this->_productRepository->get($sku);
    }
}
