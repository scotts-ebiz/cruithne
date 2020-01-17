<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 8/23/19
 * Time: 11:00 AM
 */

namespace SMG\Checkout\Plugin\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;

class GuestPaymentInformationManagementInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    public function __construct(LoggerInterface $logger,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository)
    {
        $this->_logger = $logger;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
    }

    public function beforeSavePaymentInformationAndPlaceOrder(\Magento\Checkout\Api\GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null)
    {
        $this->_logger->error("Start....GuestPaymentInformationMangementInterface");

        try
        {
            $this->_logger->error("CartId: " . $cartId);

            // get the quote information
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

            if (empty($quoteIdMask))
            {
                $this->_logger->debug("$quoteIdMask is empty");
            }

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());



            // if the billing address is present then update the phone
            if ($billingAddress)
            {
                $this->_logger->error("There is a billing address.");

                // get the phone number
                $billingPhone = $billingAddress->getTelephone();

                $this->_logger->error("Billing Phone: " . $billingPhone);

                // replace the dashes with empty so the correct value is stored in the database
                $newBillingPhone = str_replace('-', '', $billingPhone);

                $this->_logger->error("New Billing Phone: " . $newBillingPhone);

                // set the new phone number
                $billingAddress->setTelephone($newBillingPhone);
            }

            // get the shipping address from the quote address information
            // check to see if the shipping address is available.  it should be but just in case check for it
            $quoteShippingAddress = $quote->getShippingAddress();
            if ($quoteShippingAddress)
            {
                $this->_logger->error("There is a quote shipping address.");

                // get the phone number
                $quoteShippingPhone = $quoteShippingAddress->getTelephone();

                $this->_logger->error("Quote Shipping Phone: " . $quoteShippingPhone);

                // replace the dashes with empty so the correct value is stored in the database
                $newQuoteShippingPhone = str_replace('-', '', $quoteShippingPhone);

                $this->_logger->error("New Quote Shipping Phone: " . $newQuoteShippingPhone);

                // set the new phone number
                $quoteShippingAddress->setTelephone($newQuoteShippingPhone);
            }

            // get the billing address from the address information
            // check to see if the billing address is available.
            $quoteBillingAddress = $quote->getBillingAddress();
            if ($quoteBillingAddress)
            {
                $this->_logger->error("There is a quote billing address.");

                // get the phone number
                $quoteBillingPhone = $quoteBillingAddress->getTelephone();

                $this->_logger->error("Quote Billing Phone: " . $quoteBillingPhone);

                // replace the dashes with empty so the correct value is stored in the database
                $newQuoteBillingPhone = str_replace('-', '', $quoteBillingPhone);

                $this->_logger->error("New Quote Billing Phone: " . $newQuoteBillingPhone);

                // set the new phone number
                $quoteBillingAddress->setTelephone($newQuoteBillingPhone);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        $this->_logger->error("CartId: " . $cartId);
        $this->_logger->error("Email: " . $email);
        $this->_logger->error("Done....GuestPaymentInformationMangementInterface");

        

        // return
        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}