<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Model\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use SMG\Sales\Model\Order;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem\CollectionFactory as SubscriptionOrderItemCollectionFactory;

/**
 * Class SubscriptionOrder
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionOrder extends AbstractModel
{

    /** @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var SubscriptionOrderItemCollectionFactory */
    protected $_subscriptionOrderItemCollectionFactory;

    /** @var OrderRepository */
    protected $_orderRepository;

    /** @var Order */
    protected $_order;

    /** @var CreditmemoCollectionFactory */
    protected $_creditmemoFactory;

    /** @var CreditmemoService */
    private $_creditmemoService;

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder::class
        );
    }

    /**
     * SubscriptionOrder constructor.
     * @param Context $context
     * @param Registry $registry
     * @param SubscriptionHelper $subscriptionHelper
     * @param SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory
     * @param OrderRepository $orderRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoService $creditmemoService
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory,
        OrderRepository $orderRepository,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_subscriptionOrderItemCollectionFactory = $subscriptionOrderItemCollectionFactory;
        $this->_orderRepository = $orderRepository;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_creditmemoService = $creditmemoService;
    }

    /**
     * Generate the shipment dates for the subscription order
     * @throws \Exception
     */
    public function generateShipDates() {
        if ( is_null($this->getShipStartDate()) || $this->getShipStartDate() == '0000-00-00 00:00:00' ) {
            $this->generateShipStartDate();
            $this->generateShipEndDate();
        }
    }

    /**
     * Get subscription orders
     * @return mixed
     */
    public function getSubscriptionOrderItems()
    {

        // Make sure we have an actual subscription
        if ( is_null( $this->getEntityId() ) ) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if ( ! isset($this->_subscriptionOrderItems) ) {
            $subscriptionOrderItems = $this->_subscriptionOrderItemCollectionFactory->create();
            $subscriptionOrderItems->addFieldToFilter( 'subscription_order_entity_id', $this->getEntityId() );
            $this->_subscriptionOrderItems = $subscriptionOrderItems;
        }

        return $this->_subscriptionOrderItems;
    }

    /**
     * Generate Ship Start Date
     * @throws \Exception
     */
    private function generateShipStartDate() {

        // Grab the shipment open window from the admin
        $shippingOpenWindow = 0;
        if ( ! empty($this->_subscriptionHelper->getShipDaysStart()) ) {
            $shippingOpenWindow = filter_var( $this->_subscriptionHelper->getShipDaysStart(), FILTER_SANITIZE_NUMBER_INT );
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipStartDate = new \DateTime($this->getApplicationStartDate());
        $earliestShipStartDate->sub(new \DateInterval('P' . $shippingOpenWindow . 'D'));
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        if ( $todayDate <= $earliestShipStartDate ) {
            $this->setShipStartDate( $earliestShipStartDate );
        } else {
            $this->setShipStartDate( $todayDate );
        }
    }

    /**
     * Generate Ship End Date
     * @throws \Exception
     */
    private function generateShipEndDate() {

        // Grab the shipment open window from the admin
        $shippingCloseWindow = 0;
        if ( ! empty($this->_subscriptionHelper->getShipDaysEnd()) ) {
            $shippingCloseWindow = filter_var( $this->_subscriptionHelper->getShipDaysEnd(), FILTER_SANITIZE_NUMBER_INT );
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipEndDate = new \DateTime( $this->getApplicationEndDate() );
        $earliestShipEndDate->sub( new \DateInterval('P' . $shippingCloseWindow . 'D') );
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        if ( $todayDate <= $earliestShipEndDate ) {
            $this->setShipEndDate( $earliestShipEndDate->format( 'Y-m-d H:i:s' ) );
        } else {
            $this->setShipEndDate( $todayDate->format( 'Y-m-d H:i:s' )  );
        }

        $this->save();
    }

    /**
     * Create Credit Memo for Order
     * 
     * @param int $order_id
     * @throws LocalizedException
     */
    public function createCreditMemo( $order_id ) {
       try {
           /** @var Order $order */
           $order = $this->_orderRepository->get( $order_id );
           $invoices = $order->getInvoiceCollection();

           /** @var Invoice $invoice */
           foreach ( $invoices as $invoice ) {
                $invoiceIncrementId = $invoice->getIncrementId();
           }

           $invoiceData = $invoice->loadByIncrementId( $invoiceIncrementId );
            /** @var Creditmemo $creditmemo */
           $creditmemo = $this->_creditmemoFactory->createByOrder( $order );
           $creditmemo->setInvoice( $invoiceData );
           $this->_creditmemoService->refund( $creditmemo );
       } catch ( \Exception $e ) {
           throw new LocalizedException( __('Could not create credit memo for order.') );
       }
    }
}