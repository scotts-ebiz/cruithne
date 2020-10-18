<?php

namespace SMG\BackendService\Plugin;

use \Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\ResultFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\App\Response\RedirectInterface;
use \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;

class BeforeCreateCreditMemo
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * BeforeCreateCreditMemo constructor.
     * @param OrderFactory $orderFactory
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param RedirectInterface $reidrect
     */
    public function __construct(
        OrderFactory $orderFactory,
        ManagerInterface $messageManager,
        ResultFactory $resultFactory,
        RedirectInterface $redirect
    ) {
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->redirect = $redirect;
    }

    /**
     * @param CreditmemoLoader $order
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function beforeLoad(CreditmemoLoader $order)
    {
        $orderId = $order->getOrderId();

        if ($orderId) {
            $order = $this->orderFactory->create()->load($orderId);
            $apiResponse = false;
            if ($order->getState() !== 'complete' && $apiResponse == true) {
                if ($apiResponse == true) {
                    return;
                }
            } else {
                $message = 'Credit Memo cannot be created.';
                $this->messageManager->addError($message);

                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setUrl('sales/*/view', ['order_id' => $orderId]);
            }
        }
    }
}
