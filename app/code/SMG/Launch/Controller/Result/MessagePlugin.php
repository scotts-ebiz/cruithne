<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Launch\Controller\Result;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Translate\Inline\ParserInterface;
use Magento\Framework\Translate\InlineInterface;
/**
 * Plugin for putting messages to cookies
 */
class MessagePlugin
{
    /**
     * Cookies name for messages
     */
    const MESSAGES_COOKIES_NAME = 'mage-messages';
	const ERRORMESSAGES_COOKIES_NAME = 'mage-dtmerror-messages';	
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\View\Element\Message\InterpretationStrategyInterface
     */
    private $interpretationStrategy;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;
	protected $_logger;
    /**
     * @var InlineInterface
     */
    private $inlineTranslate;

	protected $_checkoutSession;
    /**
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $interpretationStrategy
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param InlineInterface|null $inlineTranslate
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $interpretationStrategy,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        InlineInterface $inlineTranslate = null
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->messageManager = $messageManager;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->interpretationStrategy = $interpretationStrategy;
        $this->inlineTranslate = $inlineTranslate ?: ObjectManager::getInstance()->get(InlineInterface::class);
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
        ResultInterface $subject,
        ResultInterface $result
    ) {
        $messages = $this->getCookiesMessages();
        /** @var MessageInterface $message */
		if(count($messages) > 0){
			$emsg = array();
			foreach ($this->messageManager->getMessages(true)->getItems() as $message) {
				if($message->getType() == 'error'){
					$emsg[] = $this->interpretationStrategy->interpret($message);	
				}
			}
		
			if(count($emsg) > 0){
				$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
				$publicCookieMetadata->setDurationOneYear();
				$publicCookieMetadata->setPath('/');
				$publicCookieMetadata->setHttpOnly(false);

				$this->cookieManager->setPublicCookie(
					self::ERRORMESSAGES_COOKIES_NAME,
					$this->serializer->serialize($emsg),
					$publicCookieMetadata
				);
			}
		}
        return $result;
    }
	protected function getCookiesMessages()
    {
        $messages = $this->cookieManager->getCookie(self::MESSAGES_COOKIES_NAME);
        if (!$messages) {
            return [];
        }
        $messages = $this->serializer->unserialize($messages);
        if (!is_array($messages)) {
            $messages = [];
        }
        return $messages;
    }

}
