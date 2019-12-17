<?php
namespace SMG\SubscriptionAccounts\Block;


class Settings extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formKey;

    /**
     * Subscriptions block constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
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
        return $this->_customerSession->getCustomer()->getData();
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
