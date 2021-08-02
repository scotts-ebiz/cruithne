<?php
/**
 * User: cnixon
 * Date: 7/27/21
 * Time: 3:00 PM
 */

namespace SMG\Sales\Controller\Adminhtml\MassAction;

use Magento\Backend\App\Action;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use SMG\Api\Helper\OrderStatusHelper;

class MassUnholdAndInvoice extends Action {
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * @var OrderStatusHelper
     */
    protected $_orderStatusHelper;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * MassUnholdAndInvoice constructor.
     * @param Action\Context $context
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(
        Action\Context $context,
        OrderCollectionFactory $orderCollectionFactory,
        OrderStatusHelper $orderStatusHelper
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_orderStatusHelper = $orderStatusHelper;
        parent::__construct($context);
    }

    public function execute() {
        $request = $this->getRequest();

        $orderIds = $request->getPost('selected', []);

        if (empty($orderIds)) {
            $this->getMessageManager()->addErrorMessage(__('No orders found.'));
            return $this->_redirect('sales/order/index');
        }

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        try {
            /** @var Order $order */
            foreach ($orderCollection as $order) {
                if ($order->canUnhold()) {
                    $order->unhold();
                }

                $this->_orderStatusHelper->createInvoice($order);
            }
        } catch (\Exception | \Throwable $e ) {
            $message = "An error occurred while changing selected orders.";
            $this->getMessageManager()->addErrorMessage(__($message));
            // Rehold order.
            try {
                if ($order->canHold()) {
                    $order->hold();
                }
            } catch (\Exception $e) {
                // Well, we tried.
            }
        }

        return $this->_redirect('sales/order/index');
    }
}
