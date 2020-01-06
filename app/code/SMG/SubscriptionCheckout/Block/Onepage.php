<?php
namespace SMG\SubscriptionCheckout\Block;

use Magento\Framework\Session\SessionManagerInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription;

/**
 * Onepage checkout block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Onepage extends \Magento\Checkout\Block\Onepage
{
    /** @var Subscription  */
    private $_subscription;

    /** @var SessionManagerInterface */
    private $_coreSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Checkout\Model\CompositeConfigProvider $configProvider
     * @param SessionManagerInterface $coreSession
     * @param Subscription $subscription
     * @param array $layoutProcessors
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\Serialize\SerializerInterface $serializerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        SessionManagerInterface $coreSession,
        Subscription $subscription,
        array $layoutProcessors = [],
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        parent::__construct($context, $formKey, $configProvider, $layoutProcessors, $data, $serializer, $serializerInterface);

        $this->_coreSession = $coreSession;
        $this->_subscription = $subscription;
    }

    /**
     * Get Subscription or Redirect
     */
    public function getSubscription()
    {
        // Check to see if subscription already exists
        try {
            $subscription = $this->_subscription->getSubscriptionByQuizId($this->_coreSession->getQuizId());
            $data = $subscription->convertToArray();
            $addOn = $subscription->getAddOn();
            $data['is_shippable'] = $subscription->isCurrentlyShippable();
            $data['add_on'] = $addOn ? $addOn->convertToArray() : false;

            return json_encode($data);
        } catch (\Exception $e) {
            header("Location: /quiz");
        }
    }
}
