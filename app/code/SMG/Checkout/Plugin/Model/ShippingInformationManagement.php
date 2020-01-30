<?php

namespace SMG\Checkout\Plugin\Model;

use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;

class ShippingInformationManagement
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;
    protected $_checkoutSession;
    protected $_productloader;
    protected $_messageManager;
    protected $_cart;
    protected $_quoteRepository;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\ProductFactory $_productloader
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(\Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_productloader = $_productloader;
        $this->_urlInterface = $urlInterface;
        $this->_messageManager = $messageManager;
        $this->_cart = $cart;
        $this->_quoteRepository = $quoteRepository;
        $this->_logger = $logger;
    }

    public function beforeSaveAddressInformation(\Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation)
    {
        try
        {
            // get the shipping address from the address information
            // check to see if the shipping address is available.  it should be but just in case check for it
            $shippingAddress = $addressInformation->getShippingAddress();
            if ($shippingAddress)
            {
                // get the phone number
                $shippingPhone = $shippingAddress->getTelephone();

                // replace the dashes with empty so the correct value is stored in the database
                $newShippingPhone = str_replace('-', '', $shippingPhone);

                // set the new phone number
                $shippingAddress->setTelephone($newShippingPhone);
            }

            // get the billing address from the address information
            // check to see if the billing address is available.
            $billingAddress = $addressInformation->getBillingAddress();
            if ($billingAddress)
            {
                // get the phone number
                $billingPhone = $billingAddress->getTelephone();

                // replace the dashes with empty so the correct value is stored in the database
                $newBillingPhone = str_replace('-', '', $billingPhone);

                // set the new phone number
                $billingAddress->setTelephone($newBillingPhone);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        return [$cartId, $addressInformation];
    }

    public function afterSaveAddressInformation(\Magento\Checkout\Model\ShippingInformationManagement $shipping, $result)
    {
        $items = $this->_cart->getQuote()->getAllItems();
        $validate = false;
        $State= $this->_checkoutSession->getQuote()->getShippingAddress()->getRegion();

        foreach($items as $item) {
            $itemId = $item-> getItemId();
            $productId=$item->getProductId();
            $product=$this->_productloader->create()->load($productId);
            $productname[] = $product->getName();
            $statesNotAllowed = $product->getData('state_not_allowed');
            $data = explode(',', $statesNotAllowed);
            $option_value = array();

            foreach($data as $value)
            {
                $attr = $product->getResource()->getAttribute('state_not_allowed');
                $option_value[] = $attr->getSource()->getOptionText($value);
            }

            if(in_array($State, $option_value)) {
                $validate = true;
                $this->_cart->removeItem($itemId)->save();
            }
        }

        if($validate) {
            $quoteId = $this->_checkoutSession->getQuote()->getId();
            $quote = $this->_quoteRepository->get($quoteId);
            $this->_quoteRepository->save($quote);
            $homepage = $this->_urlInterface->getBaseUrl();
            $checkout = $this->_urlInterface->getUrl('checkout/cart', ['_secure' => true]);
            $message="Unfortunately one or more of the selected products is restricted from shipping to " . $State .
                ". The item has been removed from the cart.";

            throw new InputException(__($message));
        }

        return  $result;
    }
}
