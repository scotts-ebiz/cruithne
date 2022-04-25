<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\ShipTracking\Block\Sales\Email\Shipment;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order\Shipment\Track as TrackItem;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\NoSuchEntityException;
use SMG\ShipTracking\Model\ConfigProvider;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\ShipmentRepository;

/**
 * Class Track
 * @package SMG\ShipTracking\Block\Sales\Email\Shipment
 */
class Track extends Template
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    private $orderRepository;

    private $shipmentRepository;

    /**
     * @param Template\Context $context
     * @param ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        OrderRepository $orderRepository,
        ShipmentRepository $shipmentRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;

        $this->configProvider = $configProvider;
    }

    /**
     * @param TrackItem $trackItem
     * @return Phrase|string
     * @throws NoSuchEntityException
     */
    public function getTrackingHtml(TrackItem $trackItem)
    {
        /** @var TrackItem $trackItem */
        $trackingUrl = $this->configProvider->getTrackingUrlForService(
            $this->_storeManager->getStore()->getId(),
            $trackItem->getCarrierCode()
        );

        if ($trackingUrl) {
            return sprintf(
                '<a target="_blank" href="%s">%s</a>',
                str_replace(
                    '{{code}}',
                    $trackItem->getNumber(),
                    $trackingUrl
                ),
                $trackItem->getTitle()
            );
        }

        return __($trackItem->getTitle());
    }

    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }

    public function getShipment($id)
    {
        return $this->shipmentRepository->get($id);
    }
}
