<?php
namespace SMG\SubscriptionCheckout\Block;

use Magento\Framework\Session\SessionManagerInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\RecommendationApi\Api\Recommendation;

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
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var Recommendation
     */
    protected $_recommendation;



    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Checkout\Model\CompositeConfigProvider $configProvider
     * @param SessionManagerInterface $coreSession
     * @param Subscription $subscription
     * @param RecurlyHelper $recurlyHelper
     * @param Recommendation $recommendation
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
        RecurlyHelper $recurlyHelper,
        Recommendation $recommendation,
        array $layoutProcessors = [],
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        parent::__construct($context, $formKey, $configProvider, $layoutProcessors, $data, $serializer, $serializerInterface);

        $this->_coreSession = $coreSession;
        $this->_subscription = $subscription;
        $this->_recurlyHelper = $recurlyHelper; 
        $this->_recommendation = $recommendation; 
    }

    /**
     * Get the Recurly Public API Key.
     *
     * @return string
     */
    public function getRecurlyPublicApiKey()
    {
        return $this->_recurlyHelper->getRecurlyPublicApiKey();
    }

    
    // public function testFunctionOne()
    // {
    //     $testVar = 'asdfasdf';
	//     return $testVar;
    // }
    


    public function quizResultString()
    {
        $retieveQuizResults = $this->_recommendation->getResult('1342', 'a2854c92-3dd1-445d-af94-e1e1c3d8ad78', '80016');

        // $retieveQuizResults = $this->_recommendation->getResult();

        // $exampleResult = array(
        //     'id' => 'asdfasdf',
        //     'plan' => array('0' => 'One', '1' => 'Two')
        // );

        $getResultId = $retieveQuizResults['0']['plan']['coreProducts']['0']['applicationStartDate'];


        $results = $getResultId;


        // $getResultId = $retieveQuizResults['id'];


        // $testVar = ['One','Two','Five'];
        
        // $getImplodedQuizResultValue = $this->implodeQuizResult($retieveQuizResults);
        
        return $results;
    }


    public function implodeQuizResult($value)
    {
	    $quizResultArrayValue = $value;
	    $implodedQuizResultArrayValue = implode(' ', $quizResultArrayValue);
	    $value = $implodedQuizResultArrayValue;
        return $value;
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
            $data['is_shippable'] = $subscription->getData('subscription_type') == 'annual' || $subscription->isCurrentlyShippable();
            $data['add_on'] = $addOn ? $addOn->convertToArray() : false;

            return json_encode($data);
        } catch (\Exception $e) {
            $this->_logger->error('Could not find subscription in checkout with quiz ID: ' . $this->_coreSession->getQuizId() . '.');
            header("Location: /quiz");
        }
    }
}
