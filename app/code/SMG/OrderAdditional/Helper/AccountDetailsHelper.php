<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 5/17/19
 * Time: 10:56 AM
 */

namespace SMG\OrderAdditional\Helper;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;

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
    protected $transactionRepository;

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
     */
    public function __construct(OrderRepositoryInterface $orderRepository,
        SapOrderResource $sapOrderResource,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepository $transactionRepository)
    {
        $this->_orderRepository = $orderRepository;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_filterBuilder = $filterBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
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

        // initialize the return array to have empty values for viewing.
        $dataobj['sap_order_id']=NULL;
        $dataobj['order_status']=NULL;
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

        // get the order info
        $order = $this->_orderRepository->get($orderId);
        $dataobj['order_id'] = $order->getRealOrderId();
        $dataobj['additional_information'] = json_encode($order->getPayment()->getAdditionalInformation());
        $dataobj['shipping_description'] = $order->getShippingDescription();

        // get the SAP Order Info
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);
        $dataobj['sap_order_id'] = $sapOrder->getSapOrderId();
        $dataobj['order_status'] = $sapOrder->getOrderStatus();
        $dataobj['delivery_number'] = $sapOrder->getDeliveryNumber();

        // get the SAP Order Item info
        $sapOrderItems = $sapOrder->getSapOrderItems();
        foreach($sapOrderItems as $sapOrderItem)
        {
            // get the desired values
            $shipTrackingNumber = $sapOrderItem->getData('ship_tracking_number');
            $invoiceNumber = $sapOrderItem->getData('sap_billing_doc_number');
            $fulfillmentLocation = $sapOrderItem->getData('fulfillment_location');

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
        }

        $dataobj['tracking'] = implode(',', $tracking);
        $dataobj['sap_billing_doc_number'] = implode(',', $invoices);
        $dataobj['fulfillment_location'] = implode(',', $fulfillmentLocations);;

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
        $transactionList = $this->transactionRepository->getList($searchCriteria);
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
