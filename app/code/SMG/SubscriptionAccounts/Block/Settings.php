<?php

namespace SMG\SubscriptionAccounts\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Settings
 * @package SMG\SubscriptionAccounts\Block
 */
class Settings extends Template
{
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * Subscriptions block constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        FormKey $formKey,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * Return customer data to use it in the frontend form
     *
     * @return array
     */
    public function getCustomerData()
    {
        $data = [];
        $allData = $this->_customerSession->getCustomer()->getData();
        $data['email'] = $allData['email'];
        $data['firstname'] = $allData['firstname'];
        $data['lastname'] = $allData['lastname'];

        return $data;
    }

    /**
     * Get the form action URL for POST the save request
     *
     * @return string
     */
    public function saveFormAction()
    {
        return '/account/settings/save';
    }

    /**
     * Return customer id
     *
     * @return string
     */
    private function getCustomerId()
    {
        return $this->_customerSession->getCustomer()->getId();
    }

    /**
     * Return form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }
}
