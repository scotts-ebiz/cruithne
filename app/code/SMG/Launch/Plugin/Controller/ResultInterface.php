<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Launch\Plugin\Controller;

/**
 * Plugin for putting messages to cookies
 */
class ResultInterface
{
    /**
     * Cookies name for messages
     */
    const MESSAGES_COOKIES_NAME = 'mage-messages';
	const ERRORMESSAGES_COOKIES_NAME = 'mage-dtmerror-messages';	
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $_cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $_cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     * @var \Magento\Framework\View\Element\Message\InterpretationStrategyInterface
     */
    private $_interpretationStrategy;
	
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $_serializer;

    /**
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $interpretationStrategy
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer

     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $interpretationStrategy,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        $this->_cookieManager = $cookieManager; 
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_messageManager = $messageManager;
        $this->_serializer = $serializer;
        $this->_interpretationStrategy = $interpretationStrategy;
    }

    /**
     * Set 'mage-messages' cookie
     *
     * Checks the result that controller actions must return. If result is not JSON type, then
     * sets 'mage-messages' cookie.
     *
     * @param ResultInterface $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterRenderResult(
        \Magento\Framework\Controller\ResultInterface $subject,
        \Magento\Framework\Controller\ResultInterface $result
    ) {
        $messages = $this->getCookiesMessages();
        /** @var MessageInterface $message */
		if(count($messages) > 0){
			$emsg = array();
			foreach ($this->_messageManager->getMessages(true)->getItems() as $message) {
				if($message->getType() == 'error'){
					$emsg[] = $this->_interpretationStrategy->interpret($message);	
				}
			}
		
			if(count($emsg) > 0){
				$publicCookieMetadata = $this->_cookieMetadataFactory->createPublicCookieMetadata();
				$publicCookieMetadata->setDurationOneYear();
				$publicCookieMetadata->setPath('/');
				$publicCookieMetadata->setHttpOnly(false);

				$this->_cookieManager->setPublicCookie(
					self::ERRORMESSAGES_COOKIES_NAME,
					$this->_serializer->serialize($emsg),
					$publicCookieMetadata
				);
			}
		}
        return $result;
    }
	protected function getCookiesMessages()
    {
        $messages = $this->_cookieManager->getCookie(self::MESSAGES_COOKIES_NAME);
        if (!$messages) {
            return [];
        }
        $messages = $this->_serializer->unserialize($messages);
        if (!is_array($messages)) {
            $messages = [];
        }
        return $messages;
    }

}
