<?php
/**
 * Checkout Rewrite Checkout Cart Index Controller
 *
 * @category    SMG
 * @package     SMG_Checkout
 *
 */
namespace SMG\Checkout\Plugin\Controller\Cart;

use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

class Index
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(LoggerInterface $logger,
        Session $session)
    {
        $this->_logger = $logger;
        $this->_session = $session;
    }

    /**
     * After the page is created then we need to determine if there
     * is anything in the cart so we know to go to an empty cart or
     * the acutal checkout cart
     *
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Checkout\Controller\Cart\Index $subject, $result)
    {
        try
        {
            // get the quote from the session
            $items = $this->_session->getQuote()->getAllVisibleItems();

            // determine if there is anything on the cart
            if (count($items) === 0)
            {
                $result->getConfig()->getTitle()->set(__('Your Cart is Empty'));
                $result->getConfig()->addBodyClass('empty-cart-page');
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->debug($e->getMessage());
        }

        // return
        return $result;
    }
}
