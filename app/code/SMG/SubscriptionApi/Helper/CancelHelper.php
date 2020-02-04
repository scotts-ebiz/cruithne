<?php

namespace SMG\SubscriptionApi\Helper;

use Exception;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Model\RecurlySubscription;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionModel;
use SMG\SubscriptionApi\Model\Subscription;

class CancelHelper extends AbstractHelper
{
    /**
     * @var RecurlySubscription
     */
    protected $_recurlySubscription;

    /**
     * @var SubscriptionModel
     */
    protected $_subscriptionModel;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * CancelHelper constructor.
     * @param Context $context
     * @param RecurlySubscription $recurlySubscription
     */
    public function __construct(
        Context $context,
        RecurlySubscription $recurlySubscription,
        SubscriptionModel $subscriptionModel,
        AddressRepositoryInterface $addressRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->_recurlySubscription = $recurlySubscription;
        $this->_subscriptionModel = $subscriptionModel;
        $this->_addressRepository = $addressRepository;
        $this->_logger = $logger;
    }

    /**
     * @param bool $cancelActive
     * @param bool $cancelFutuer
     * @param null $accountCode
     * @return false|string
     * @throws LocalizedException
     */
    public function cancelSubscriptions($cancelActive = true, $cancelFutuer = true, $accountCode = null)
    {
        // Cancel the Recurly Subscriptions.
        try {
            // Cancel recurly subscriptions
            $cancelledSubscriptionIds = $this->_recurlySubscription->cancelRecurlySubscriptions(true, true, $accountCode);

            // Find the master subscription id
            $masterSubscriptionId = null;
            foreach ($cancelledSubscriptionIds as $planCode => $cancelledSubscriptionId) {
                if (in_array($planCode, ['annual', 'seasonal'])) {
                    $masterSubscriptionId = $cancelledSubscriptionId;
                }
            }

            if (is_null($masterSubscriptionId)) {
                $error = "Couldn't find the master subscription id.";
                $this->_logger->error($error);
                throw new LocalizedException(__($error));
            }

            // Find the subscription
            /** @var Subscription $subscription */
            $subscription = $this->_subscriptionModel->getSubscriptionByMasterSubscriptionId($masterSubscriptionId);

            if (! $subscription) {
                $error = 'Could not find subscription with ID ' . $masterSubscriptionId;
                $this->_logger->error($error);
                throw new LocalizedException(_($error));
            }

            // Cancel subscription orders
            $subscription->cancelSubscriptions($this->_recurlySubscription);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(_('There was an issue cancelling subscriptions.'));
        }

        // We canceled the subscription, so clear customer addresses.
        $customer = $subscription->getCustomer();

        if ($customer) {
            $this->clearCustomerAddresses($customer);
        }

        return true;
    }

    /**
     * Delete customer addresses, because we don't want to store them in the address book,
     * so they will always need to enter their shipping/billing details on checkout
     *
     * @param Customer $customer
     */
    private function clearCustomerAddresses($customer)
    {
        $customer->setDefaultBilling(null);
        $customer->setDefaultShipping(null);

        try {
            foreach ($customer->getAddresses() as $address) {
                $this->_addressRepository->deleteById($address->getId());
            }

            $customer->cleanAllAddresses();
            $customer->save();
        } catch (NoSuchEntityException $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        } catch (LocalizedException $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        } catch (\Exception $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        }
    }
}
