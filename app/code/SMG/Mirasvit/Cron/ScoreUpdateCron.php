<?php

namespace SMG\Mirasvit\Cron;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mirasvit\FraudCheck\Model\Score;
use Mirasvit\FraudCheck\Model\ScoreFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use SMG\Api\Helper\OrderStatusHelper;

class ScoreUpdateCron
{
    const XML_FRAUD_CHECK_CONFIG_CRON_PATH = 'fraud_check/cron/';

    /**
     * @var OrderStatusHelper
     */
    protected $_orderStatusHelper;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var ScoreFactory
     */
    private $scoreFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * ScoreUpdateCron constructor.
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ScoreFactory $scoreFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ScoreFactory $scoreFactory,
        ScopeConfigInterface $scopeConfigInterface,
        OrderStatusHelper $orderStatusHelper
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scoreFactory           = $scoreFactory;
        $this->scopeConfigInterface   = $scopeConfigInterface;
        $this->_orderStatusHelper = $orderStatusHelper;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if ($this->getActiveFromConfig()) {
            $collection = $this->orderCollectionFactory->create();

            $collection->addFieldToFilter('fraud_score', ['null' => true])
                ->addFieldToFilter('created_at', ['gteq' => $this->getDateFromConfig()])
                ->setPageSize($this->getBatchSizeConfig())
                ->setOrder('created_at', 'desc');

            foreach ($collection as $order) {
                if (!$order->getPayment()) {
                    continue;
                }

                try {
                    $score = $this->scoreFactory->create();

                    $score->setOrder($order)
                        ->getFraudScore();

                    // for update status
                    $order = $order->load($order->getId());
                    $score->setOrder($order);
                } catch (\Exception $e) {
                    // skip score update for that order
                }
            }
            /** @var Order $order */
            foreach ($collection as $order) {
                $fraudStatus = $order->getData('fraud_status');
                if ($fraudStatus === Score::STATUS_APPROVE) {
                    try {
                        $this->_orderStatusHelper->createInvoice($order);
                    } catch (\Exception | \Throwable $e ) {
                        try {
                            if ($order->canHold()) {
                                $order->hold();
                            }
                        } catch (\Exception $e) {
                            // I give up
                        }
                    }
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getBatchSizeConfig()
    {
        return $this->scopeConfigInterface->getValue(self::XML_FRAUD_CHECK_CONFIG_CRON_PATH . 'batch_size');
    }

    /**
     * @return mixed
     */
    public function getDateFromConfig()
    {
        return $this->scopeConfigInterface->getValue(self::XML_FRAUD_CHECK_CONFIG_CRON_PATH . 'date_from');
    }

    /**
     * @return mixed
     */
    public function getActiveFromConfig()
    {
        return $this->scopeConfigInterface->getValue(self::XML_FRAUD_CHECK_CONFIG_CRON_PATH . 'active');
    }
}
