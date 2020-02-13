<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 2/11/20
 * Time: 9:54 AM
 */

namespace SMG\Gigya\Helper;


class GigyaMageHelper extends \Gigya\GigyaIM\Helper\GigyaMageHelper
{
    /**
     * We had to override because Gigya was forcing first name and lastname
     * was required.  There was no flag that could have been checked, so the best
     * option was overriding and remove the required fields option for first and last
     * name.
     *
     * @param GigyaUser $gigya_user_account
     *
     * @return array $message (validation errors messages)
     */
    public function verifyGigyaRequiredFields($gigya_user_account)
    {
        $message = [];
        $loginId = $gigya_user_account->getGigyaLoginId();
        if (empty($loginId)) {
            $this->gigyaLog(__FUNCTION__ . "Gigya user does not have email in [loginIDs][emails] array");
            array_push($message, __('Email not supplied. please make sure that your social account provides an email, or contact our support'));
        }
        return $message;
    }
}