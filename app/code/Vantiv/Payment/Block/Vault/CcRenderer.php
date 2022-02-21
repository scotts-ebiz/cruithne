<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use Magento\Payment\Model\CcConfigProvider;
use Magento\Framework\View\Element\Template\Context;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig;
use Vantiv\Payment\Helper\Vault as VaultHelper;

class CcRenderer extends AbstractCardRenderer
{
    /**
     * Vault helper
     *
     * @var VaultHelper
     */
    private $vaultHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CcConfigProvider $iconsProvider
     * @param VaultHelper $vaultHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CcConfigProvider $iconsProvider,
        VaultHelper $vaultHelper,
        array $data = []
    ) {
        parent::__construct($context, $iconsProvider, $data);
        $this->vaultHelper = $vaultHelper;
    }

    /**
     * Get Vault helper instance
     *
     * @return VaultHelper
     */
    protected function getVaultHelper()
    {
        return $this->vaultHelper;
    }

    /**
     * Check if can render current token.
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === VantivCcConfig::METHOD_CODE
            && $this->getVaultHelper()->isCcVaultEnabled();
    }

    /**
     * Get credit card number last 4 digits.
     *
     * @return string
     */
    public function getNumberLast4Digits()
    {
        $details = $this->getTokenDetails();
        return $details['ccLast4'];
    }

    /**
     * Get expiration date.
     *
     * @return string
     */
    public function getExpDate()
    {
        $details = $this->getTokenDetails();
        $expMonth = $details['ccExpMonth'];
        $expYear = $details['ccExpYear'];
        $expDate = $expMonth . '/' . $expYear;
        return $expDate;
    }

    /**
     * Get card icon data.
     *
     * @param string
     * @return mixed
     */
    private function getIconData($key)
    {
        $details = $this->getTokenDetails();
        $type = $details['ccType'] == 'AX' ? 'AE' : $details['ccType'];
        $iconData = $this->getIconForType($type);

        return array_key_exists($key, $iconData) ? $iconData[$key] : null;
    }

    /**
     * Get icon URL.
     *
     * @return string
     */
    public function getIconUrl()
    {
        $url = $this->getIconData('url');
        return $url;
    }

    /**
     * Get icon height.
     *
     * @return int
     */
    public function getIconHeight()
    {
        $height = $this->getIconData('height');
        return $height;
    }

    /**
     * Get icon width.
     *
     * @return int
     */
    public function getIconWidth()
    {
        $width = $this->getIconData('width');
        return $width;
    }

    /**
     * Get "Edit" URL.
     *
     * @return string
     */
    public function getEditUrl()
    {
        $params = [
            'public_hash' => $this->getToken()->getPublicHash()
        ];
        return $this->getUrl('vantiv/vault/ccform', $params);
    }
}
