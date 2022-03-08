<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Observer\Paypal;

use Magento\GoogleAnalytics\Block\Ga;
use Vantiv\Payment\Helper\Paypal\Shortcut\Factory;
use Magento\Framework\Event\ObserverInterface;
use Vantiv\Payment\Model\Paypal\Config as PaypalConfig;
use Magento\Framework\Event\Observer as EventObserver;

/**
 * PayPal module observer
 */
class AddPaypalShortcutsObserver implements ObserverInterface
{
    /**
     * @var Factory
     */
    protected $shortcutFactory;

    /**
     * @var Vantiv Paypal Config
     */
    protected $paypalConfig;

    /**
     * Constructor
     *
     * @param Factory $shortcutFactory
     * @param PaypalConfig $paypalConfig
     */
    public function __construct(
        Factory $shortcutFactory,
        PaypalConfig $paypalConfig
    ) {
        $this->shortcutFactory = $shortcutFactory;
        $this->paypalConfig = $paypalConfig;
    }

    /**
     * Add PayPal shortcut buttons
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();
        $blocks = [
            'Vantiv\Payment\Block\Paypal\Express\Shortcut' => PaypalConfig::METHOD_CODE,
        ];
        foreach ($blocks as $blockInstanceName => $paymentMethodCode) {
            if (!$this->paypalConfig->isMethodAvailable($paymentMethodCode)) {
                continue;
            }

            $params = [
                'shortcutValidator' => $this->shortcutFactory->create($observer->getEvent()->getCheckoutSession()),
            ];
            if (!in_array('Bml', explode('\\', $blockInstanceName))) {
                $params['checkoutSession'] = $observer->getEvent()->getCheckoutSession();
            }

            // we believe it's \Magento\Framework\View\Element\Template
            $shortcut = $shortcutButtons->getLayout()->createBlock(
                $blockInstanceName,
                '',
                $params
            );
            $shortcut->setIsInCatalogProduct(
                $observer->getEvent()->getIsCatalogProduct()
            )->setShowOrPosition(
                $observer->getEvent()->getOrPosition()
            );
            $shortcutButtons->addShortcut($shortcut);
        }
    }
}
