<?php

namespace SMG\SubscriptionApi\Api;

use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Customer;
use SMG\SubscriptionApi\Helper\SeasonalHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface as CoreSession;
use SMG\SubscriptionApi\Api\Interfaces\RecurlyInterface;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Helper\SubscriptionOrderHelper;
use SMG\SubscriptionApi\Model\RecurlySubscription as RecurlySubscriptionModel;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionModel;

/**
 * Class RecurlySubscription
 * @package SMG\SubscriptionApi\Api
 */
class RecurlySubscription implements RecurlyInterface
{
    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_addressRepository;

    /** @var RecurlySubscriptionModel  */
    protected $_recurlySubscriptionModel;

    /** @var SubscriptionModel */
    private $_subscriptionModel;

    /** @var CoreSession */
    private $_coreSession;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SubscriptionOrderHelper
     */
    protected $_subscriptionOrderHelper;

    /**
     * RecurlySubscription constructor.
     * @param AddressRepositoryInterface $addressRepository
     * @param RecurlySubscriptionModel $recurlySubscriptionModel
     * @param SubscriptionModel $subscriptionModel
     * @param CoreSession $coreSession
     * @param LoggerInterface $logger
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param SeasonalHelper $seasonalHelper
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        RecurlySubscriptionModel $recurlySubscriptionModel,
        SubscriptionModel $subscriptionModel,
        CoreSession $coreSession,
        LoggerInterface $logger,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        SeasonalHelper $seasonalHelper
    ) {
        $this->_addressRepository = $addressRepository;
        $this->_recurlySubscriptionModel = $recurlySubscriptionModel;
        $this->_subscriptionModel = $subscriptionModel;
        $this->_coreSession = $coreSession;
        $this->_logger = $logger;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_seasonalHelper = $seasonalHelper;
    }

    /**
     * Create new Recurly subscription for the customer. Use it's existing Recurly account if there is one,
     * otherwise create new Recurly account for the customer
     *
     * @param string $token
     * @return string|array
     *
     * @api
     */
    public function createRecurlySubscription($token)
    {
        try {

            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscriptionModel->getSubscriptionByQuizId($this->_coreSession->getQuizId());
            $subscription->createSubscriptionService($token, $this->_recurlySubscriptionModel);

            return json_encode([
                'success' => true,
                'message' => 'Subscription successfully created.'
            ]);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());

            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if the customer already has a Recurly subscription
     *
     * @api
     */
    public function checkRecurlySubscription()
    {
        return $this->_recurlySubscriptionModel->checkRecurlySubscription();
    }

    /**
     * Cancel customer Recurly Subscription
     *
     * @api
     */
    public function cancelRecurlySubscription()
    {
        // Cancel Recurly Subscriptions
        try {
            // Cancel recurly subscriptions
            $cancelledSubscriptionIds = $this->_recurlySubscriptionModel->cancelRecurlySubscriptions(true, true);

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
            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscriptionModel->getSubscriptionByMasterSubscriptionId($masterSubscriptionId);

            // Cancel subscription orders
            $subscription->cancelSubscriptions($this->_recurlySubscriptionModel);
        } catch (LocalizedException $e) {
            $this->_logger->error($e->getMessage());

            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        // We canceled the subscription, so clear customer addresses.
        $customer = $subscription->getCustomer();

        if ($customer) {
            $this->clearCustomerAddresses($customer);
        }

        return json_encode([
            'success' => true,
            'message' => 'Subscriptions successfully cancelled.'
        ]);
    }

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @api
     */
    public function processSeasonalInvoice()
    {
        $this->_seasonalHelper->processSeasonalOrders();
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
