<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace SMG\Launch\Plugin\CustomerData;

/**
 * Messages section
 */
class Messages
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

    
    public function __construct(
		\Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
		\Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
		$this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
		$this->_serializer = $serializer;
    }
	
	
	
	public function afterGetSectionData(\Magento\Theme\CustomerData\Messages $subject, $result)
    {
			$messages = $result['messages'];
		
			if(count($messages) > 0){
				$emsg = array();
				foreach ($messages as $message) {
					if($message['type'] == 'error'){
						$emsg[] = $message['text'];	
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
}
