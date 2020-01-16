<?php
namespace SMG\Customer\Plugin\Model;
class EmailNotification
{
    public function aroundNewAccount(\Magento\Customer\Model\EmailNotification $subject, \Closure $proceed)
    {
    return $subject;
    }
}
