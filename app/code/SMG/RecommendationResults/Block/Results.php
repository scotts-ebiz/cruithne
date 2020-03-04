<?php
namespace SMG\RecommendationResults\Block;

use Magento\Framework\Session\SessionManagerInterface;
class Results extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper
     */
    protected $_helper;
    
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * Quiz constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        SessionManagerInterface $coreSession
    ) {
        parent::__construct($context, $data);
        $this->_coreSession = $coreSession;
    }
    
    /**
     * @return string
     */
    public function getQuizId(){
         $this->_coreSession->start();
        return $this->_coreSession->getData('quiz_id');
    }
    
    /**
     * @return string
     */
    public function getZipCode(){
         $this->_coreSession->start();
        return $this->_coreSession->getZipCode();
    }

    public function getNewId(){
         $timestamp = strtotime(date("Y-m-d H:i:s"));
         return $this->_coreSession->getTimeStamp().'-'.$timestamp;
    }  
     
}
