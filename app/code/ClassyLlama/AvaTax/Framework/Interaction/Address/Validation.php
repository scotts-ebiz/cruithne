<?php
/**
 * ClassyLlama_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2016 Avalara, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ClassyLlama\AvaTax\Framework\Interaction\Address;

use AvaTax\SeverityLevel;
use AvaTax\TextCase;
use AvaTax\ValidateRequestFactory;
use ClassyLlama\AvaTax\Exception\AddressValidateException;
use ClassyLlama\AvaTax\Framework\Interaction\Address;
use ClassyLlama\AvaTax\Framework\Interaction\Cacheable\AddressService;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Validation
{
    /**
     * @var Address
     */
    protected $interactionAddress = null;

    /**
     * @var AddressService
     */
    protected $addressService = null;

    /**
     * @var ValidateRequestFactory
     */
    protected $validateRequestFactory = null;

    /**
     * Error message to use when response does not contain error messages
     */
    const GENERIC_VALIDATION_MESSAGE = 'An unknown address validation error occurred';

    /**
     * @param Address $interactionAddress
     * @param AddressService $addressService
     * @param ValidateRequestFactory $validateRequestFactory
     */
    public function __construct(
        Address $interactionAddress,
        AddressService $addressService,
        ValidateRequestFactory $validateRequestFactory
    ) {
        $this->interactionAddress = $interactionAddress;
        $this->addressService = $addressService;
        $this->validateRequestFactory = $validateRequestFactory;
    }

    /**
     * Validate address using AvaTax Address Validation API
     *
     * @param array|\Magento\Customer\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|/AvaTax/ValidAddress|\Magento\Customer\Api\Data\AddressInterface|\Magento\Quote\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|array|null
     * @param $storeId
     * @return array|\Magento\Customer\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|/AvaTax/ValidAddress|\Magento\Customer\Api\Data\AddressInterface|\Magento\Quote\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|array|null
     * @throws AddressValidateException
     * @throws LocalizedException
     */
    public function validateAddress($addressInput, $storeId)
    {
        $returnCoordinates = 1;
        $validateRequest = $this->validateRequestFactory->create(
            [
            'address' => $this->interactionAddress->getAddress($addressInput),
                'textCase' => (TextCase::$Mixed ? TextCase::$Mixed : TextCase::$Default),
                'coordinates' => $returnCoordinates,
            ]
        );
        $validateResult = $this->addressService->validate($validateRequest, $storeId);

        if ($validateResult->getResultCode() == SeverityLevel::$Success) {
            $validAddresses = $validateResult->getValidAddresses();

            if (isset($validAddresses[0])) {
                $validAddress = $validAddresses[0];
            } else {
                return null;
            }
            // Convert data back to the type it was passed in as
            switch (true) {
                case ($addressInput instanceof \Magento\Customer\Api\Data\AddressInterface):
                    $validAddress = $this->interactionAddress
                        ->convertAvaTaxValidAddressToCustomerAddress($validAddress, $addressInput);
                    break;
                case ($addressInput instanceof \Magento\Quote\Api\Data\AddressInterface):
                    $validAddress = $this->interactionAddress
                        ->convertAvaTaxValidAddressToQuoteAddress($validAddress, $addressInput);
                    break;
                default:
                    throw new LocalizedException(__(
                        'Input parameter "$addressInput" was not of a recognized/valid type: "%1".',
                        [gettype($addressInput),]
                    ));
                    break;
            }

            return $validAddress;
        } else {
            $messages = $validateResult->getMessages();
            $firstMessage = array_shift($messages);
            $message = $firstMessage instanceof \AvaTax\Message
                ? $firstMessage->getSummary()
                : self::GENERIC_VALIDATION_MESSAGE;
            throw new AddressValidateException(__($message));
        }
    }
}
