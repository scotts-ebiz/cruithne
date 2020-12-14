<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 5/17/19
 * Time: 10:56 AM
 */

namespace SMG\Sales\Helper;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\SapOrderStatusFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\ResourceModel\SapOrderStatus as SapOrderStatusResource;

class AccountDetailsHelper
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var ShippingConditionCodeFactory
     */
    protected $_shippingConditionCodeFactory;

    /**
     * @var ShippingConditionCodeResource
     */
    protected $_shippingConditionCodeResource;

    /**
     * @var FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var TransactionRepository
     */
    protected $_transactionRepository;

    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SapOrderStatusFactory
     */
    protected $_sapOrderStatusFactory;

    /**
     * @var SapOrderStatusResource
     */
    protected $_sapOrderStatusResource;

    /**
     * AccountDetailsHelper constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SapOrderResource $sapOrderResource
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepository $transactionRepository
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param SapOrderStatusFactory $sapOrderStatusFactory
     * @param SapOrderStatusResource $sapOrderStatusResource
     */
    public function __construct(OrderRepositoryInterface $orderRepository,
        SapOrderResource $sapOrderResource,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepository $transactionRepository,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource,
        SapOrderStatusFactory $sapOrderStatusFactory,
        SapOrderStatusResource $sapOrderStatusResource)
    {
        $this->_orderRepository = $orderRepository;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_filterBuilder = $filterBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_transactionRepository = $transactionRepository;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_sapOrderStatusFactory = $sapOrderStatusFactory;
        $this->_sapOrderStatusResource = $sapOrderStatusResource
        ;
    }

    /**
     * Get the Order Detail Information
     *
     * @param $orderId
     * @return array
     */
    public function getOrderInfo($orderId)
    {
        $dataobj = [];
        $tracking = [];
        $invoices = [];
        $fulfillmentLocations = [];
        $deliveryNumbers = [];

        // initialize the return array to have empty values for viewing.
        $dataobj['sap_order_id']=NULL;
        $dataobj['order_status']=NULL;
        $dataobj['order_sent']=NULL;
        $dataobj['order_sent_date']=NULL;
        $dataobj['sap_billing_doc_number']=NULL;
        $dataobj['additional_information']=NULL;
        $dataobj['cc_authorization_transaction'] = NULL;
        $dataobj['cc_capture_transaction'] = NULL;
        $dataobj['cc_response'] = NULL;
        $dataobj['shipping_description'] = NULL;
        $dataobj['sap_shipping_code'] = NULL;
        $dataobj['tracking'] = NULL;
        $dataobj['delivery_number'] = NULL;
        $dataobj['invoice_id'] = NULL;
        $dataobj['fulfillment_location'] = NULL;
        $dataobj['subscription_order_id'] = NULL;
        $dataobj['subscription_type'] = NULL;
        $dataobj['subscription_ship_start'] = NULL;
        $dataobj['subscription_ship_end'] = NULL;

        // get the order info
        $order = $this->_orderRepository->get($orderId);
        $dataobj['order_id'] = $order->getRealOrderId();
        $dataobj['additional_information'] = json_encode($order->getPayment()->getAdditionalInformation());
        $dataobj['shipping_description'] = $order->getShippingDescription();
        $dataobj['subscription_type'] = $order->getSubscriptionType();
        $dataobj['subscription_order_id'] = $order->getSubscriptionId();
        $dataobj['subscription_ship_start'] = $order->getShipStartDate();
        $dataobj['subscription_ship_end'] = $order->getShipEndDate();
        $dataobj['scotts_customer_id'] = $order->getScottsCustomerId();
        $dataobj['ls_order_id'] = $order->getLsOrderId();
        $dataobj['parent_order_id'] = $order->getParentOrderId();

        // get the SAP Order Info
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);
        $dataobj['sap_order_id'] = $sapOrder->getSapOrderId();

        // get the order status
        /**
         * @var \SMG\Sap\Model\SapOrderStatus $sapOrderStatus
         */
        $sapOrderStatus = $this->_sapOrderStatusFactory->create();
        $this->_sapOrderStatusResource->load($sapOrderStatus, $sapOrder->getOrderStatus());

        // make sure that the status was found
        $statusLabel = $sapOrderStatus->getData('label');
        if (!isset($statusLabel))
        {
            $statusLabel = $sapOrder->getOrderStatus();
        }

        // set the order status with the appropriate value
        $dataobj['order_status'] = $statusLabel;

        // determine if the order was sent to SAP
        /**
         * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
         */
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $this->_sapOrderBatchResource->load($sapOrderBatch, $orderId, 'order_id');

        $orderProcessDate = $sapOrderBatch->getData('order_process_date');

        if (!empty($orderProcessDate))
        {
            $dataobj['order_sent'] = 'Yes';
            $dataobj['order_sent_date'] = $orderProcessDate;
        }
        else
        {
            $dataobj['order_sent'] = 'No';
        }

        // get the SAP Order Item info
        $sapOrderItems = $sapOrder->getSapOrderItems();
        /**
         * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
         */
        foreach($sapOrderItems as $sapOrderItem)
        {
            // get the list of shipments
            $sapOrderShipments = $sapOrderItem->getSapOrderShipments($sapOrderItem->getId());

            // loop through the shipments for the following information
            /**
             * @var \SMG\Sap\Model\SapOrderShipment $sapOrderShipment
             */
            foreach ($sapOrderShipments as $sapOrderShipment)
            {
                // get the desired values
                $shipTrackingNumber = $sapOrderShipment->getData('ship_tracking_number');
                $invoiceNumber = $sapOrderShipment->getData('sap_billing_doc_number');
                $fulfillmentLocation = $sapOrderShipment->getData('fulfillment_location');
                $deliveryNumber = $sapOrderShipment->getData('delivery_number');

                // we don't want duplicates to show
                if (!in_array($shipTrackingNumber, $tracking))
                {
                    $tracking[] = $shipTrackingNumber;
                }

                if (!in_array($invoiceNumber, $invoices))
                {
                    $invoices[] = $invoiceNumber;
                }

                if (!in_array($fulfillmentLocation, $fulfillmentLocations))
                {
                    $fulfillmentLocations[] = $fulfillmentLocation;
                }

                if (!in_array($deliveryNumber, $deliveryNumbers))
                {
                    $deliveryNumbers[] = $deliveryNumber;
                }
            }
        }

        $dataobj['tracking'] = implode(',', $tracking);
        $dataobj['sap_billing_doc_number'] = implode(',', $invoices);
        $dataobj['fulfillment_location'] = implode(',', $fulfillmentLocations);
        $dataobj['delivery_number'] = implode(',', $deliveryNumbers);

        $shippingCondition = $this->_shippingConditionCodeFactory->create();
        $this->_shippingConditionCodeResource->load($shippingCondition, $order->getShippingMethod(), 'shipping_method');
        $dataobj['sap_shipping_code'] = $shippingCondition->getData('sap_shipping_method');

        // create the necessary filters for the transaction repository
        $filters[] = $this->_filterBuilder->setField('payment_id')
            ->setValue($order->getPayment()->getId())
            ->create();
        $filters[] = $this->_filterBuilder->setField('order_id')
            ->setValue($orderId)
            ->create();
        $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filters)
            ->create();

        // get the list of transactions for the order
        $transactionList = $this->_transactionRepository->getList($searchCriteria);
        if($transactionList)
        {
            foreach($transactionList as $transactionObj)
            {
                if($transactionObj->getTxnType() == "authorization")
                {
                    $dataobj['cc_authorization_transaction'] = $transactionObj->getTxnId();
                }

                if($transactionObj->getTxnType() == "capture")
                {
                    $dataobj['cc_capture_transaction'] = $transactionObj->getTxnId();
                }

                $response = json_encode($transactionObj->getAdditionalInformation());
                $objresponse = json_decode($response);
                if($objresponse->{'raw_details_info'}->{'response'} == '000')
                {
                    $dataobj['cc_response'] = 'Approved';
                }
                else
                {
                    $dataobj['cc_response'] = 'Declined';
                }
            }
        }

        // return
        return $dataobj;
    }
}
