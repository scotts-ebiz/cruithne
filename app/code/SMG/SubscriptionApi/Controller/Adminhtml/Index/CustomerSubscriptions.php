<?php
namespace SMG\SubscriptionApi\Controller\Adminhtml\Index;
 
class CustomerSubscriptions extends \Magento\Customer\Controller\Adminhtml\Index
{

    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
    
}