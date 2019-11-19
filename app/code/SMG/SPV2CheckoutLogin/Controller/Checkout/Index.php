<?php

namespace SMG\SPV2CheckoutLogin\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Checkout\Controller\Index\Index implements HttpGetActionInterface
{

    public function execute()
    {

        $checkoutHelper = $this->_objectManager->get(\Magento\Checkout\Helper\Data::class);
        $urlHelper = $this->_objectManager->get(\Magento\Framework\Url\Helper\Data::class);
        $quote = $this->getOnepage()->getQuote();

        /**
         * If the customer is not logged in and guest checkout is not allowed,
         * redirect the customer to the login page. Set current URL (/checkout) as referer,
         * so the customer is redirected to checkout page on successfull login.
         */
        if( ! $this->_customerSession->isLoggedIn() && ! $checkoutHelper->isAllowedGuestCheckout( $quote ) ) {
            $resultRedirect = $this->resultRedirectFactory->create();

            $customerLoginUrl = $this->_url->getUrl( 
                'customer/account/login',
                array(
                    'referer' => $urlHelper->getEncodedUrl( $this->_url->getCurrentUrl() )
                )
            );

            return $resultRedirect->setPath($customerLoginUrl);
        }

        if ( ! $this->isSecureRequest() ) {
            $this->_customerSession->regenerateId();
        }

        $this->_objectManager->get(\Magento\Checkout\Model\Session::class)->setCartWasUpdated(false);
        $this->getOnepage()->initCheckout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Checkout'));
        return $resultPage;
    }

    private function isSecureRequest(): bool
    {
        $request = $this->getRequest();

        $referrer = $request->getHeader('referer');
        $secure = false;

        if ($referrer) {
            $scheme = parse_url($referrer, PHP_URL_SCHEME);
            $secure = $scheme === 'https';
        }

        return $secure && $request->isSecure();
    }
}
