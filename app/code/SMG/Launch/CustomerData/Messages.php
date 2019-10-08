<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace SMG\Launch\CustomerData;

use Magento\Framework\App\ObjectManager;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;

/**
 * Messages section
 */
class Messages implements SectionSourceInterface
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
	
	 private $_serializer;

   /**
     * Manager messages
     *
     * @var MessageManager
     */ 
    protected $_messageManager;

    /**
     * @var InterpretationStrategyInterface
     */
    private $_interpretationStrategy;

    /**
     * Constructor
     *
     * @param MessageManager $messageManager
     * @param InterpretationStrategyInterface $interpretationStrategy
     */
    public function __construct(
		\Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
		\Magento\Framework\Serialize\Serializer\Json $serializer = null,
        MessageManager $messageManager,
        InterpretationStrategyInterface $interpretationStrategy
    ) {
		$this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
		$this->_serializer = $serializer ?: ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->_messageManager = $messageManager;
        $this->_interpretationStrategy = $interpretationStrategy;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {	
        $messages = $this->_messageManager->getMessages(true);
		
		if(count($messages) > 0){
			$emsg = array();
			foreach ($messages->getItems() as $message) {
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
		
        return [
            'messages' => array_reduce(
                $messages->getItems(),
                function (array $result, MessageInterface $message) {
                    $result[] = [
                        'type' => $message->getType(),
                        'text' => $this->_interpretationStrategy->interpret($message)
                    ];
                    return $result;
                },
                []
            ),
        ];
    }
}
