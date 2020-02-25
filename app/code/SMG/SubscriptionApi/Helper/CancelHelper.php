<?php

namespace SMG\SubscriptionApi\Helper;

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Zaius\Engage\Helper\Sdk as ZaiusSdk;

class CancelHelper extends AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var ZaiusSdk
     */
    protected $_sdk;

    /**
     * @var CustomerCollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * CancelHelper constructor.
     * @param Context $context
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CustomerSession $customerSession
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param LoggerInterface $logger
     * @param ZaiusSdk $sdk
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerSession $customerSession,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        LoggerInterface $logger,
        ZaiusSdk $sdk
    ) {
        parent::__construct($context);

        $this->_addressRepository = $addressRepository;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_logger = $logger;
        $this->_sdk = $sdk;
    }

    /**
     * @param string $accountCode
     * @return false|string
     * @throws LocalizedException
     */
    public function cancelSubscriptions($accountCode = '')
    {
        // Get the current user.
        try {
            if (! $accountCode) {
                $accountCode = $this->_customerSession->getCustomer()->getData('gigya_uid');
            }

            $subscription = $this->_subscriptionCollectionFactory
                ->create()
                ->addFieldToFilter('gigya_id', $accountCode)
                ->addFieldToFilter('subscription_status', 'active')
                ->fetchItem();

            $customer = $this->_customerCollectionFactory
                ->create()
                ->addFieldToFilter('gigya_uid', $accountCode)
                ->fetchItem();

            $subscription->cancel();

            $this->clearCustomerAddresses($customer);
            $this->zaiusCancelCall($customer->getData('email'));
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__('There was an error while cancelling the subscription.'));
        }
    }

    /**
     * Delete customer addresses, because we don't want to store them in the address book,
     * so they will always need to enter their shipping/billing details on checkout
     *
     * @param $customer
     */
    private function clearCustomerAddresses($customer)
    {
        try {
            $customer->setDefaultBilling(null);
            $customer->setDefaultShipping(null);

            foreach ($customer->getAddresses() as $address) {
                $this->_addressRepository->deleteById($address->getId());
            }

            $customer->cleanAllAddresses();
            $customer->save();
        } catch (Exception $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        }
    }

    /**
     * Cancel subscription from zaius
     * @param $customer_email
     * @throws \ZaiusSDK\ZaiusException
     */
    private function zaiusCancelCall($customer_email)
    {
        $zaiusstatus = false;

        // check isSubcription and shipmentstatus
        if ($customer_email) {
            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();
            // take event as a array and add parameters
            $event = [];
            $event['type'] = 'subscription';
            $event['action'] = 'cancelled';
            $event['identifiers'] = ['email'=>$customer_email];

            // get postevent function
            $zaiusstatus = $zaiusClient->postEvent($event);

            // check return values from the postevent function
            if ($zaiusstatus) {
                $this->_logger->debug("The customer Email Subscription " . $customer_email . " is cancelled successfully to zaius."); //saved in var/log/debug.log
            } else {
                $this->_logger->error("The customer Email Subscription " . $customer_email . " is failed to zaius.");
            }
        }
    }
}
