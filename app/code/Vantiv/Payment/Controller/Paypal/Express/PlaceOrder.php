<?php
/**
 *
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Controller\Paypal\Express;

use Magento\Framework\Exception\LocalizedException;
use Magento\Paypal\Model\Api\ProcessableException as ApiProcessableException;

class PlaceOrder extends AbstractExpress
{
    /**
     * @var \Magento\Checkout\Api\AgreementsValidatorInterface
     */
    protected $agreementsValidator;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Vantiv\Payment\Model\Paypal\Express\Checkout\Factory $checkoutFactory
     * @param \Magento\Framework\Session\Generic $paypalSession
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Vantiv\Payment\Model\Paypal\Express\Checkout\Factory $checkoutFactory,
        \Magento\Framework\Session\Generic $paypalSession,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator
    ) {
        $this->agreementsValidator = $agreementValidator;
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $orderFactory,
            $checkoutFactory,
            $paypalSession,
            $urlHelper,
            $customerUrl
        );
    }

    /**
     * Submit the order
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute()
    {
        if ($this->isValidationRequired() &&
            !$this->agreementsValidator->isValid(array_keys($this->getRequest()->getPost('agreement', [])))
        ) {
            $e = new LocalizedException(
                __('Please agree to all the terms and conditions before placing the order.')
            );
            $this->messageManager->addExceptionMessage(
                $e,
                $e->getMessage()
            );
            $this->_redirect('*/*/review');
            return;
        }

        try {
            $this->_initCheckout();
            $this->_checkout->place($this->_initToken());

            // Prepare session to success or cancellation page
            $this->_getCheckoutSession()->clearHelperData();

            // Last successful quote
            $quoteId = $this->_getQuote()->getId();
            $this->_getCheckoutSession()->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            // An order may be created
            $order = $this->_checkout->getOrder();
            if ($order) {
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $this->_eventManager->dispatch(
                'paypal_express_place_order_success',
                [
                    'order' => $order,
                    'quote' => $this->_getQuote()
                ]
            );

            // redirect if PayPal specified some URL (for example, to Giropay bank)
            $url = $this->_checkout->getRedirectUrl();
            if ($url) {
                $this->getResponse()->setRedirect($url);
                return;
            }
            $this->_initToken(false); // no need in token anymore
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (ApiProcessableException $e) {
            $this->_processPaypalApiError($e);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t place the order.')
            );
            $this->_redirect('*/*/review');
        }
    }

    /**
     * Process PayPal API's processable errors
     *
     * @param \Vantiv\Payment\Model\Paypal\Api\ProcessableException $exception
     * @return void
     */
    protected function _processPaypalApiError($exception)
    {
        switch ($exception->getCode()) {
            case ApiProcessableException::API_MAX_PAYMENT_ATTEMPTS_EXCEEDED:
            case ApiProcessableException::API_TRANSACTION_EXPIRED:
                $this->getResponse()->setRedirect(
                    $this->_getQuote()->getPayment()->getCheckoutRedirectUrl()
                );
                break;
            case ApiProcessableException::API_DO_EXPRESS_CHECKOUT_FAIL:
                $this->_redirectSameToken();
                break;
            case ApiProcessableException::API_ADDRESS_MATCH_FAIL:
                $this->redirectToOrderReviewPageAndShowError($exception->getUserMessage());
                break;
            case ApiProcessableException::API_UNABLE_TRANSACTION_COMPLETE:
                $this->_redirectSameToken();
                break;
            default:
                $this->_redirectToCartAndShowError($exception->getUserMessage());
                break;
        }
    }

    /**
     * Redirect customer back to PayPal with the same token
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _redirectSameToken()
    {
        $token = $this->_initToken();
        $this->getResponse()->setRedirect(
            $this->_config->getExpressCheckoutStartUrl($token)
        );
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $this->_redirect('checkout/cart');
    }

    /**
     * Redirect customer to the paypal order review page and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    private function redirectToOrderReviewPageAndShowError($errorMessage)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $this->_redirect('*/*/review');
    }

    /**
     * Return true if agreements validation required
     *
     * @return bool
     */
    protected function isValidationRequired()
    {
        return is_array($this->getRequest()->getBeforeForwardInfo())
        && empty($this->getRequest()->getBeforeForwardInfo());
    }
}
