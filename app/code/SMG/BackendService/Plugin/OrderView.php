<?php
namespace SMG\BackendService\Plugin;

use Magento\Framework\UrlInterface;
use \Magento\Framework\Message\ManagerInterface;

class OrderView
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * OrderView constructor.
     * @param ManagerInterface $messageManager
     * @param UrlInterface $url
     */
    public function __construct(
        ManagerInterface $messageManager,
        UrlInterface $url
    ) {
        $this->messageManager = $messageManager;
        $this->url = $url;
    }

    /**
     * @param \Magento\Sales\Controller\Adminhtml\Order\View $subject
     */
    public function beforeExecute(
        \Magento\Sales\Controller\Adminhtml\Order\View $subject
    ) {
        if (strpos($this->url->getCurrentUrl(), 'creditmemoerror') !== false) {
            $message = 'Credit Memo cannot be created.';
            $this->messageManager->addError($message);
            return;
        }
    }
}
