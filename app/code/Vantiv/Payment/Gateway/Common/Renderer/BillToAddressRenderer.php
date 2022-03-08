<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Vantiv <billToAddress> XML document renderer.
 *
 * @api
 */
class BillToAddressRenderer extends AbstractRenderer
{
    /**
     * Render <billToAddress> XML node.
     *
     * <billToAddress>
     *     <name>NAME</name>
     *     <firstName>FIRST_NAME</firstName>
     *     <lastName>LAST_NAME</lastName>
     *     <addressLine1>ADDRESS_LINE_1</addressLine1>
     *     <addressLine2>ADDRESS_LINE_2</addressLine2>
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
    public function render(array $subject)
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('billToAddress');
        {
            $writer->writeElement('name', $this->readName($subject));
            $writer->writeElement('firstName', $this->readFirstName($subject));
            $writer->writeElement('lastName', $this->readLastName($subject));
            $writer->writeElement('companyName', $this->readCompanyName($subject));
            $writer->writeElement('addressLine1', $this->readAddressLine1($subject));
            $writer->writeElement('addressLine2', $this->readAddressLine2($subject));
            $writer->writeElement('city', $this->readCity($subject));
            $writer->writeElement('state', $this->readState($subject));
            $writer->writeElement('zip', $this->readZip($subject));
            $writer->writeElement('country', $this->readCountry($subject));
            $writer->writeElement('email', $this->readEmail($subject));
            $writer->writeElement('phone', $this->readPhone($subject));
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * Read billing name.
     *
     * @param array $subject
     * @return string
     */
    private function readName(array $subject)
    {
        $name = $this->readDataOrNull($subject, 'name');

        if ($name === null) {
            $name = $this->readFirstName($subject) . ' ' . $this->readLastName($subject);
        }

        return $name;
    }

    /**
     * Read firstName.
     *
     * @param array $subject
     * @return null|string
     */
    private function readFirstName(array $subject)
    {
        return $this->readDataOrNull($subject, 'firstName');
    }

    /**
     * Read lastName.
     *
     * @param array $subject
     * @return null|string
     */
    private function readLastName(array $subject)
    {
        return $this->readDataOrNull($subject, 'lastName');
    }

    /**
     * Read companyName.
     *
     * @param array $subject
     * @return null|string
     */
    private function readCompanyName(array $subject)
    {
        return $this->readDataOrNull($subject, 'companyName');
    }

    /**
     * Read addressLine1.
     *
     * @param array $subject
     * @return null|string
     */
    private function readAddressLine1(array $subject)
    {
        return $this->readDataOrNull($subject, 'addressLine1');
    }

    /**
     * Read addressLine2.
     *
     * @param array $subject
     * @return null|string
     */
    private function readAddressLine2(array $subject)
    {
        return $this->readDataOrNull($subject, 'addressLine2');
    }

    /**
     * Read city.
     *
     * @param array $subject
     * @return null|string
     */
    private function readCity(array $subject)
    {
        return $this->readDataOrNull($subject, 'city');
    }

    /**
     * Read state.
     *
     * @param array $subject
     * @return null|string
     */
    private function readState(array $subject)
    {
        return $this->readDataOrNull($subject, 'state');
    }

    /**
     * Read zip.
     *
     * @param array $subject
     * @return null|string
     */
    private function readZip(array $subject)
    {
        return $this->readDataOrNull($subject, 'zip');
    }

    /**
     * Read country.
     *
     * @param array $subject
     * @return null|string
     */
    private function readCountry(array $subject)
    {
        return $this->readDataOrNull($subject, 'country');
    }

    /**
     * Read email.
     *
     * @param array $subject
     * @return null|string
     */
    private function readEmail(array $subject)
    {
        return $this->readDataOrNull($subject, 'email');
    }

    /**
     * Read phone.
     *
     * @param array $subject
     * @return null|string
     */
    private function readPhone(array $subject)
    {
        return $this->readDataOrNull($subject, 'phone');
    }
}
