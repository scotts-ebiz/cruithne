<?php
namespace SMG\RecommendationQuiz\Block;

class Quiz extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper
     */
    protected $_helper;

    /**
     * Quiz constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * @return string
     */
    public function getGoogleMapsApiKey(){
        return $this->_helper->getGoogleMapsApiKey();
    }
}
