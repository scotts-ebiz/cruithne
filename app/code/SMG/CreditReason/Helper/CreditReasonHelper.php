<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 5/14/19
 * Time: 9:06 AM
 */

namespace SMG\CreditReason\Helper;

use SMG\CreditReason\Model\CreditReasonCodeFactory;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode as CreditReasonCodeReource;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode\CollectionFactory as CreditReasonCodeCollectionFactory;

class CreditReasonHelper
{
    /**
     * @var CreditReasonCodeFactory
     */
    protected $_creditReasonCodeFactory;

    /**
     * @var CreditReasonCodeReource
     */
    protected $_creditReasonCodeResource;

    /**
     * @var CreditReasonCodeCollectionFactory
     */
    protected $_creditReasonCodeCollectionFactory;

    /**
     * Constructor.
     *
     * @param CreditReasonCodeFactory $creditReasonCodeFactory
     * @param CreditReasonCodeReource $creditReasonCodeResource
     * @param CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory
     */
    public function __construct(CreditReasonCodeFactory $creditReasonCodeFactory,
        CreditReasonCodeReource $creditReasonCodeResource,
        CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory)
    {
        $this->_creditReasonCodeFactory = $creditReasonCodeFactory;
        $this->_creditReasonCodeResource = $creditReasonCodeResource;
        $this->_creditReasonCodeCollectionFactory = $creditReasonCodeCollectionFactory;
    }

    /**
     * Get the list of Credit Reason Codes
     *
     * @return CreditReasonCodeReource\Collection
     */
    public function getCreditReasonCodes()
    {
        // Get the list of active reason codes
        $creditResonCodes = $this->_creditReasonCodeCollectionFactory->create();
        $creditResonCodes->addFieldToFilter("is_active", ["eq" => true]);

        // return the reason codes
        return $creditResonCodes;
    }

    /**
     * Gets the Short Description of the desired reason code
     *
     * @param mixed|string $creditReasonCode
     * @return mixed|string
     */
    public function getCreditReasonCode($creditReasonCode)
    {
        // Get the reason code to get the short description
        $creditReason = $this->_creditReasonCodeFactory->create();
        $this->_creditReasonCodeResource->load($creditReason, $creditReasonCode, 'reason_code');

        // get the short description and make sure it has a value
        $shortDescription = $creditReason->getData('short_desc');
        if (empty($shortDescription))
        {
            $shortDescription = 'No Reason Found';
        }

        // return the short description
        return $shortDescription;
    }
}