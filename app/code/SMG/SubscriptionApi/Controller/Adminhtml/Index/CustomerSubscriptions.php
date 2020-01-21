<?php
namespace SMG\SubscriptionApi\Controller\Adminhtml\Index;

/**
 * Class CustomerSubscriptions
 * @package SMG\SubscriptionApi\Controller\Adminhtml\Index
 */
class CustomerSubscriptions extends \Magento\Customer\Controller\Adminhtml\Index
{

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        return $this->resultLayoutFactory->create();
    }
}
