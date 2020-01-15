<?php

namespace SMG\SubscriptionAccounts\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Reset
 * @package SMG\SubscriptionAccounts\Block
 */
class Reset extends Template
{

    /**
     * Reset block constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

}
