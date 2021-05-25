<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 8/6/19
 * Time: 3:28 PM
 */

namespace SMG\Quote\Plugin\Api;

use Psr\Log\LoggerInterface;
use SMG\Zaius\Helper\ApiCartAddHelper;
use Zaius\Engage\Helper\Sdk as Sdk;

class GuestCartItemRepositoryInterface
{
    /**
     * @var ApiCartAddHelper\
     */
    protected $_apiCartAddHelper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var sdk
     */
    protected $_sdk;

    /**
     * GuestCartItemRepositoryInterface constructor.
     *
     * @param ApiCartAddHelper $apiCartAddHelper
     * @param LoggerInterface $logger
     * @param Sdk $sdk
     */
    public function __construct(ApiCartAddHelper $apiCartAddHelper, LoggerInterface $logger, Sdk $sdk)
    {
        $this->_apiCartAddHelper = $apiCartAddHelper;
        $this->_logger = $logger;
        $this->_sdk = $sdk;
    }

    /**
     * Add a call to Zaius after an item was saved
     *
     * @param \Magento\Quote\Api\GuestCartItemRepositoryInterface $subject
     * @param $result
     * @return mixed
     */
    public function afterSave(\Magento\Quote\Api\GuestCartItemRepositoryInterface $subject, $result)
    {
        try
        {
            $this->_logger->debug("After save of item " . $result-> getProduct()->getId() . " to cart.  Trying to send product to Zaius");

            // add zaius to cart
            $this->_apiCartAddHelper->addToCart($result->getProduct(), $result->getQuoteId());
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        // return
        return $result;
    }
}