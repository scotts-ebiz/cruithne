<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/13/19
 * Time: 9:56 AM
 */

namespace SMG\Sales\Plugin\Block\Adminhtml\Order\Create\Billing\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Psr\Log\LoggerInterface;

class Form
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig)
    {
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
    }

    public function afterGetMethods(\Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $subject, $result)
    {
        // check to see if there was anything in the return
        // if there was nothing then just return the original result
        if (is_array($result) && count($result))
        {
            // get the admin portal active flag
            $adminActive = $this->_scopeConfig->getValue('payment/vantiv_cc/admin_active', ScopeInterface::SCOPE_STORE);

            // remove the credit card option if the admin active flag is not set
            if (!$adminActive)
            {
                // loop through the array to find out if the credit card exists
                foreach ($result as $key => $value)
                {
                    // get the payment code
                    $paymentCode = $value->getCode();

                    $this->_logger->debug("Code: " . $paymentCode);
                    if (!empty($paymentCode) && $paymentCode == 'vantiv_cc')
                    {
                        // remove it from the return
                        unset($result[$key]);
                    }
                }
            }
        }

        // return
        return $result;
    }
}