<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;

/**
 * Handles all the licensing related actions. Premium version handles
 * processing of customer verify license key form. Checks if the
 * license key entered by the user is a valid license key for his
 * account or not. If so then activate his license.
 *
 * Also takes care of removing the current license from the site.
 */
class LKAction extends BaseAdminAction
{
    /**
     * Execute function to execute the classes function.
     * Handles the removing the configured license and customer
     * account from the module by removing the necessary keys
     * and feeing the key.
     *
     * @throws \Exception
     */
    public function removeAccount()
    {
        if ($this->spUtility->micr()) {
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, '');
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_KEY, '');
            $this->spUtility->setStoreConfig(SPConstants::API_KEY, '');
            $this->spUtility->setStoreConfig(SPConstants::TOKEN, '');
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_LOGIN);
            $this->spUtility->setStoreConfig(SPConstants::SHOW_ADMIN_LINK, 0);
            $this->spUtility->setStoreConfig(SPConstants::SHOW_CUSTOMER_LINK, 0);
        }
        $this->spUtility->flushCache() ;
    }

    /* ===================================================================================================
                THE FUNCTIONS BELOW ARE FREE PLUGIN SPECIFIC AND DIFFER IN THE PREMIUM VERSION
       ===================================================================================================
     */

    public function execute()
    {
        /** implemented in premium version */
    }
}
