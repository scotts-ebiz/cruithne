<?php

namespace SMG\Mirasvit\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mirasvit\FraudCheck\Model\ScoreFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ScoreUpdateCron
{
    const XML_FRAUD_CHECK_CONFIG_CRON_PATH = 'fraud_check/cron/';

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
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ScoreFactory $scoreFactory,
        ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scoreFactory           = $scoreFactory;
        $this->scopeConfigInterface   = $scopeConfigInterface;
    }

    /**
     * @return void
     */
    public function execute()
    {
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
}
