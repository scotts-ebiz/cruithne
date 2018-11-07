<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Helper\Paypal;

/**
 * Paypal Data helper
 */
class Data extends \Magento\Paypal\Helper\Data
{
    /**
     * @var \Vantiv\Payment\Model\Paypal\ConfigFactory
     */
    private $configFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Paypal\Model\Billing\AgreementFactory $agreementFactory
     * @param \Magento\Paypal\Model\ConfigFactory $configFactory
     * @param \Vantiv\Payment\Model\Paypal\ConfigFactory $vantivConfigFactory
     * @param array $methodCodes
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Paypal\Model\Billing\AgreementFactory $agreementFactory,
        \Magento\Paypal\Model\ConfigFactory $configFactory,
        \Vantiv\Payment\Model\Paypal\ConfigFactory $vantivConfigFactory,
        array $methodCodes
    ) {
        parent::__construct($context, $paymentData, $agreementFactory, $configFactory, $methodCodes);
        $this->configFactory = $vantivConfigFactory;
    }
}
