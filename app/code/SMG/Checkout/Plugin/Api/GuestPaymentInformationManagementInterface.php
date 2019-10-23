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
        try
        {
            // get the quote information
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

            // if the billing address is present then update the phone
            if ($billingAddress)
            {
                // get the phone number
                $billingPhone = $billingAddress->getTelephone();

                // replace the dashes with empty so the correct value is stored in the database
                $newBillingPhone = str_replace('-', '', $billingPhone);

                // set the new phone number
                $billingAddress->setTelephone($newBillingPhone);
            }

            // get the shipping address from the quote address information
            // check to see if the shipping address is available.  it should be but just in case check for it
            $quoteShippingAddress = $quote->getShippingAddress();
            if ($quoteShippingAddress)
            {
                // get the phone number
                $quoteShippingPhone = $quoteShippingAddress->getTelephone();

                // replace the dashes with empty so the correct value is stored in the database
                $newQuoteShippingPhone = str_replace('-', '', $quoteShippingPhone);

                // set the new phone number
                $quoteShippingAddress->setTelephone($newQuoteShippingPhone);
            }

            // get the billing address from the address information
            // check to see if the billing address is available.
            $quoteBillingAddress = $quote->getBillingAddress();
            if ($quoteBillingAddress)
            {
                // get the phone number
                $quoteBillingPhone = $quoteBillingAddress->getTelephone();

                // replace the dashes with empty so the correct value is stored in the database
                $newQuoteBillingPhone = str_replace('-', '', $quoteBillingPhone);

                // set the new phone number
                $quoteBillingAddress->setTelephone($newQuoteBillingPhone);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        // return
        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}