<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Abstract XML renderer.
 */
abstract class AbstractRenderer //implements XmlRendererInterface
{
    /**
     * Render XML document.
     *
     * @param array $subject
     * @return string
     */
    abstract public function render(array $subject);

    /**
     * Read data from subject, or return null.
     *
     * @param array $subject
     * @param string $key
     * @return mixed
     */
    protected function readDataOrNull(array $subject, $key)
    {
        return array_key_exists($key, $subject) ? $subject[$key] : null;
    }

    /**
     * Read data from subject or throw exception.
     *
     * @param array $subject
     * @param string $key
     * @throws \InvalidArgumentException
     * @return mixed
     */
    protected function readDataOrException(array $subject, $key)
    {
        if (!array_key_exists($key, $subject)) {
            throw new \InvalidArgumentException(sprintf('Key "%s" is not found in subject.', $key));
        }

        return $subject[$key];
    }

    /**
     * @param XMLWriter $xmlWriter
     * @param string $nodeName
     * @param array $subject
     * @param bool $isRequired
     */
    protected function addSimpleNode(XMLWriter $xmlWriter, $nodeName, array $subject, $isRequired = false)
    {
        if ($isRequired) {
            $value = $this->readDataOrException($subject, $nodeName);
        } else {
            $value = $this->readDataOrNull($subject, $nodeName);
        }
        if ($value !== null) {
            $xmlWriter->writeElement($nodeName, $value);
        }
    }
}
