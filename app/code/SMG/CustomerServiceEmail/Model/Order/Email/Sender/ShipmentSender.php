<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Order\Email\Sender;

use SMG\CustomerServiceEmail\Model\Order\Email\Container\ShipmentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Magento\Sales\Model\Order\Email\SenderBuilder;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentSender
 * @package SMG\CustomerServiceEmail\Model\Order\Email\Sender
 */
class ShipmentSender extends Sender
{
    /**
     * @var Renderer
     */
    protected $addressRenderer;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @param Template $templateContainer
     * @param ShipmentIdentity $identityContainer
     * @param SenderBuilderFactory $senderBuilderFactory
     * @param LoggerInterface $logger
     * @param Renderer $addressRenderer
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Template $templateContainer,
        ShipmentIdentity $identityContainer,
        SenderBuilderFactory $senderBuilderFactory,
        LoggerInterface $logger,
        Renderer $addressRenderer,
        ManagerInterface $eventManager
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer
        );

        $this->identityContainer = $identityContainer;
        $this->eventManager = $eventManager;
    }

    /**
     * Sends order shipment email to the service team.
     *
     * @param array $shipments
     * @return bool
     * @throws \Exception
     */
    public function send(array $shipments)
    {
        $this->prepareShipmentTemplate($shipments);

        /** @var SenderBuilder $sender */
        $sender = $this->getSender();

        try {
            $sender->send();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Prepare email template with variables
     *
     * @param array $shipments
     * @return void
     * @throws \Exception
     */
    private function prepareShipmentTemplate(array $shipments)
    {
        $transport = ['shipments' => $shipments];
        $transportObject = new DataObject($transport);

        $this->eventManager->dispatch(
            'email_shipment_service_set_template_vars_before',
            ['sender' => $this, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());
        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());
        $templateId = $this->identityContainer->getTemplateId();
        $this->identityContainer->setCustomerEmail($this->identityContainer->getServiceTeamEmails());
        $this->templateContainer->setTemplateId($templateId);
    }
}
