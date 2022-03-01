<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Model\Paypal;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class ExpressConfigProvider
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpressConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string[]
     */
    private $methodCodes = [
        Config::METHOD_CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    private $methods = [];

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor
     *
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        PaymentHelper $paymentHelper,
        UrlInterface $urlBuilder
    ) {
        $this->localeResolver = $localeResolver;
        $this->config = $configFactory->create();
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $locale = $this->localeResolver->getLocale();

        $config = [
            'payment' => [
                'vantivPaypalExpress' => [
                    'paymentAcceptanceMarkHref' => $this->config->getPaymentMarkWhatIsPaypalUrl(
                        $this->localeResolver
                    ),
                    'paymentAcceptanceMarkSrc' => $this->config->getPaymentMarkImageUrl(
                        $locale
                    )
                ]
            ]
        ];

        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['vantivPaypalExpress']['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
            }
        }

        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getCheckoutRedirectUrl();
    }
}
