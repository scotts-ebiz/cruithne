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
            $this->_logger->debug("This is a test in afterSave cool!");

            $this->_logger->debug('Sku: '. $result->getProduct()->getId());

            $this->_apiCartAddHelper->addToCart($result->getProduct(), $result->getQuoteId());

//            $this->zaiusApiCall($result->getProduct()->getId());
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        // return
        return $result;
    }

    /**
     * Make a call to Zaius for adding to cart through the API
     *
     * @param $orderId
     */
    private function zaiusApiCall($productId)
    {
        // call getsdkclient function
        $zaiusClient = $this->_sdk->getSdkClient();

        // take event as a array and add parameters
        $event = array();
        $event['type'] = 'product';
        $event['action'] = 'add_to_cart';
        $event['product_id'] = $productId;
        $event['identifiers'] = ['email'=>'nathan.vanhoose@scotts.com'];
        $event['data'] = ['product_id'=>$productId,'magento_store_view'=>'Default Store View'];

        // get postevent function
        $zaiusstatus = $zaiusClient->postEvent($event);

        // check return values from the postevent function
        if($zaiusstatus)
        {
            $this->_logger->info("The add_to_cart product Id " . $productId . " is passed successfully to zaius."); //saved in var/log/system.log
        }
        else
        {
            $this->_logger->info("The add_to_cart product id " . $productId . " is failed to zaius."); //saved in var/log/system.log
        }
    }
}