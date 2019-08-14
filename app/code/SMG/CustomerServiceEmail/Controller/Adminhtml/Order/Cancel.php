<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\CustomerServiceEmail\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Cancel extends \Magento\Sales\Controller\Adminhtml\Order\Cancel
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::cancel';

    /**
     * Cancel order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isValidPostRequest()) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }
        $order = $this->_initOrder();
        if ($order) {
            try {
                $this->orderManagement->cancel($order->getEntityId());
                    $isCustomerNotified = true;
                    $comment = 'canceled order successfully';
                    $order->addStatusToHistory('canceled', $comment, true);
                    $order->setIsCustomerNotified(true);
                    $this->_objectManager->create('\SMG\CustomerServiceEmail\Model\OrderCancelNotifier')
                    ->notify($order);
                    $order->save();
                $this->messageManager->addSuccessMessage(__('You canceled the order.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        }
        return parent::execute();
    }
}
