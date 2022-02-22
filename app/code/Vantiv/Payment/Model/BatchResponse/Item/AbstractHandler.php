<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\BatchResponse\Item;

abstract class AbstractHandler
{
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $emailTransportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    protected $websiteId = 0;

    public function __construct(
        \Magento\Framework\Mail\Template\TransportBuilder $emailTransportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->emailTransportBuilder = $emailTransportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
    }

    /**
     * Process batch response item
     *
     * @param \SimpleXMLElement $response
     */
    abstract public function handle(\SimpleXMLElement $response);

    /**
     * Retrieve error email recipient
     *
     * @return string
     */
    abstract protected function getErrorEmailRecipient();

    /**
     * Retrieve error email sender
     *
     * @return string
     */
    abstract protected function getErrorEmailSender();

    /**
     * Retrieve error email template
     *
     * @return string
     */
    abstract protected function getErrorEmailTemplate();

    /**
     * Log error and send it over email
     *
     * @param string $errorMessage
     * @return $this
     */
    public function logError($errorMessage)
    {
        $this->getLogger()->error($errorMessage);
        $this->sendErrorEmail($errorMessage);
        return $this;
    }

    /**
     * Get logger object
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set website id as a scope for handler
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * Send error email
     *
     * @param string $errorMessage
     * @return $this
     */
    private function sendErrorEmail($errorMessage)
    {
        $emailRecipient = $this->getErrorEmailRecipient();
        $emailSender = $this->getErrorEmailSender();
        $emailTemplate = $this->getErrorEmailTemplate();
        if (!($emailRecipient && $emailSender && $emailTemplate)) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $transport = $this->emailTransportBuilder->setTemplateIdentifier($emailTemplate)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
            ->setTemplateVars(['error_message' => $errorMessage])
            ->setFrom($emailSender)
            ->addTo($emailRecipient)
            ->getTransport();

        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }
}
