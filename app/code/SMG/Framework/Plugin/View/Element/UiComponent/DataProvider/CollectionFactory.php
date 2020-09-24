<?php

namespace SMG\Framework\Plugin\View\Element\UiComponent\DataProvider;

use Closure;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory as MagentoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as SalesOrderGridCollection;
use \Magento\Framework\App\Request\Http;

class CollectionFactory
{
    /**
     * @var MessageManager
     */
    protected $_messageManager;

    /**
     * @var SalesOrderGridCollection
     */
    protected $_collection;

    /**
     * @var Http
     */
    private $request;

    public function __construct(
        MessageManager $messageManager,
        SalesOrderGridCollection $collection,
        Http $request
    ) {
        $this->_messageManager = $messageManager;
        $this->_collection = $collection;
        $this->request = $request;
    }

    public function aroundGetReport(
        MagentoCollectionFactory $subject,
        Closure $proceed,
        $requestName
    ) {
        $result = $proceed($requestName);

        if ($requestName == 'sales_order_grid_data_source') {
            if ($this->request->getActionName() != 'gridToCsv') {
                if ($result instanceof $this->_collection) {
                    $salesOrderSapTable = $this->_collection->getTable('sales_order_sap');
                    $salesOrderStatusSapTable = $this->_collection->getTable('sales_order_status_sap');
                    $subscriptionOrderTable = $this->_collection->getTable('subscription_order');
                    $subscriptionAddonOrderTable = $this->_collection->getTable('subscription_addon_order');
                    $subscriptionOrderStatusTable = $this->_collection->getTable('subscription_order_status');

                    $this->_collection->getSelect()->joinLeft(
                        ['sales_order_sap' => $salesOrderSapTable],
                        'main_table.entity_id = sales_order_sap.order_id',
                        []
                    )->joinLeft(
                        ['sales_order_status_sap' => $salesOrderStatusSapTable],
                        'sales_order_sap.order_status = sales_order_status_sap.status AND sales_order_sap.order_status != ""',
                        ['sales_order_status_sap.label AS sap_order_status']
                    )->joinLeft(
                        ['subscription_order' => $subscriptionOrderTable],
                        'main_table.entity_id = subscription_order.sales_order_id',
                        []
                    )->joinLeft(
                        ['subscription_addon_order' => $subscriptionAddonOrderTable],
                        'main_table.entity_id = subscription_addon_order.sales_order_id',
                        []
                    )->joinLeft(
                        ['subscription_order_status' => $subscriptionOrderStatusTable],
                        'subscription_order.subscription_order_status = subscription_order_status.status OR subscription_addon_order.subscription_order_status = subscription_order_status.status',
                        ['subscription_order_status.label AS subscription_status']
                    )->group(['main_table.entity_id']);

                    return $this->_collection;
                }
            }
        }

        return $result;
    }
}
