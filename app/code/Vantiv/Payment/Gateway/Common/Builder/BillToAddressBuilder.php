<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;

/**
 * Vantiv <billToAddress> XML document builder.
 *
 * @api
 */
class BillToAddressBuilder implements RequestBuilderInterface
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     */
    public function __construct(SubjectReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Build <billToAddress> XML node.
     *
     * <billToAddress>
     *     <firstName>FIRST_NAME</firstName>
     *     <lastName>LAST_NAME</lastName>
     *     <addressLine1>STREET</addressLine1>
     *     <city>CITY</city>
     *     <state>STATE</state>
     *     <zip>ZIP</zip>
     *     <country>COUNTRY</country>
     *     <email>EMAIL</email>
     *     <phone>PHONE</phone>
     * </billToAddress>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        $address = $this->reader->readOrderAdapter($subject)->getBillingAddress();

        $firstname = $address->getFirstname();
        $lastname = $address->getLastname();
        $company = $address->getCompany();
        $streetLine1 = $address->getStreetLine1();
        $streetLine2 = $address->getStreetLine2();
        $city = $address->getCity();
        $regionCode = $address->getRegionCode();
        $postcode = $address->getPostcode();
        $countryId = $address->getCountryId();
        $email = $address->getEmail();
        $telephone = $address->getTelephone();

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('billToAddress');
        {
            $writer->startElement('name');
            $writer->text($firstname . ' ' . $lastname);
            $writer->endElement();

            $writer->startElement('firstName');
            $writer->text($firstname);
            $writer->endElement();

            $writer->startElement('lastName');
            $writer->text($lastname);
            $writer->endElement();

            $writer->startElement('companyName');
            $writer->text($company);
            $writer->endElement();

            $writer->startElement('addressLine1');
            $writer->text($streetLine1);
            $writer->endElement();

            $writer->startElement('addressLine2');
            $writer->text($streetLine2);
            $writer->endElement();

            $writer->startElement('city');
            $writer->text($city);
            $writer->endElement();

            $writer->startElement('state');
            $writer->text($regionCode);
            $writer->endElement();

            $writer->startElement('zip');
            $writer->text($postcode);
            $writer->endElement();

            $writer->startElement('country');
            $writer->text($countryId);
            $writer->endElement();

            $writer->startElement('email');
            $writer->text($email);
            $writer->endElement();

            $writer->startElement('phone');
            $writer->text($telephone);
            $writer->endElement();
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * @param array $subject
     * @return array
     */
    public function extract(array $subject)
    {
        $address = $this->reader->readOrderAdapter($subject)->getBillingAddress();

        return [
            'billToAddress' => [
                'firstName' => $address->getFirstname(),
                'lastName' => $address->getLastname(),
                'companyName' => $address->getCompany(),
                'addressLine1' => $address->getStreetLine1(),
                'addressLine2' => $address->getStreetLine2(),
                'city' => $address->getCity(),
                'state' => $address->getRegionCode(),
                'zip' => $address->getPostcode(),
                'country' => $address->getCountryId(),
                'email' => $address->getEmail(),
                'phone' => $address->getTelephone(),
            ]
        ];
    }
}
