<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

/**
 * Vantiv Environment options Source Model.
 */
class VantivEnvironment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Vantiv Environment code for 'Sandbox' option.
     *
     * @var string
     */
    const SANDBOX = 'sandbox';

    /**
     * Vantiv Environment code for 'Pre-Live' option.
     *
     * @var string
     */
    const PRELIVE = 'prelive';

    /**
     * Vantiv Environment code for 'Transact Pre-Live' option.
     *
     * @var string
     */
    const TRANSACT_PRELIVE = 'transact_prelive';

    /**
     * Vantiv Environment code for 'Post-Live' option.
     *
     * @var string
     */
    const POSTLIVE = 'postlive';

    /**
     * Vantiv Environment code for 'Transact Post-Live' option.
     *
     * @var string
     */
    const TRANSACT_POSTLIVE = 'transact_postlive';

    /**
     * Vantiv Environment code for 'Production' option.
     *
     * @var string
     */
    const PRODUCTION = 'production';

    /**
     * Vantiv Environment code for 'Transact Production' option.
     *
     * @var string
     */
    const TRANSACT_PRODUCTION = 'transact_production';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SANDBOX, 'label' => __('Sandbox')],
            ['value' => self::PRELIVE, 'label' => __('Pre-Live')],
            ['value' => self::TRANSACT_PRELIVE, 'label' => __('Transact Pre-Live')],
            ['value' => self::POSTLIVE, 'label' => __('Post-Live')],
            ['value' => self::TRANSACT_POSTLIVE, 'label' => __('Transact Post-Live')],
            ['value' => self::PRODUCTION, 'label' => __('Production')],
            ['value' => self::TRANSACT_PRODUCTION, 'label' => __('Transact Production')],
        ];
    }
}
