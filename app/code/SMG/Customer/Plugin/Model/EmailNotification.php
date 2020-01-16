<?php
namespace SMG\Customer\Plugin\Model;
class EmailNotification  extends \Magento\Customer\Model\EmailNotification
{
    public function aroundNewAccount(\Magento\Customer\Model\EmailNotification $subject, \Closure $proceed)
    {
    return $subject;
    }
}