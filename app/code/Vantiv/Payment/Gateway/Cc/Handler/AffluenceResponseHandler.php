<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Handler;

use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Handle <affluence> response data.
 */
class AffluenceResponseHandler
{
    /**
     * Mass Affluence value for response validation
     *
     * @var string
     */
    const MASS_AFFLUENCE_FLAG = 'MASS AFFLUENT';

    /**
     * Affluence value for response validation
     *
     * @var string
     */
    const AFFLUENCE_FLAG = 'AFFLUENT';

    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        SubjectReader $reader,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->reader = $reader;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get subject reader.
     *
     * @return SubjectReader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Save affluence data into Customer record
     *
     * @param array $subject
     * @param Parser $parser
     * @return boolean
     */
    public function handle(array $subject, Parser $parser)
    {
        $result = true;
        $affluence = $parser->getAffluence();

        if ($affluence != '') {
            $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
            if ($customerId !== null) {
                $customer = $this->customerRepository->getById($customerId);
                $customerAffluence = ($customer->getCustomAttribute('affluence') !== null)
                    ? $customer->getCustomAttribute('affluence')->getValue() : false;
                if (!$customerAffluence
                    || !($affluence == self::MASS_AFFLUENCE_FLAG && $customerAffluence == self::AFFLUENCE_FLAG)) {
                    $customer->setCustomAttribute('affluence', $affluence);
                    $this->customerRepository->save($customer);
                }
            }
        }

        return $result;
    }
}
