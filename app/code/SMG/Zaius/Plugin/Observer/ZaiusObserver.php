<?php

namespace SMG\Zaius\Plugin\Observer;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;

class ZaiusObserver
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->_logger = $logger;
    }

    /**
     * Wrap Zaius execution in a try...catch so it doesn't affect outside code.
     *
     * @param $zaiusObserver
     * @param callable $proceed
     * @param Observer $observer
     */
    public function aroundExecute($zaiusObserver, callable $proceed, Observer $observer)
    {
        try {
            $proceed($observer);
        } catch (Exception $exception) {
            // Log and ignore any exceptions from Zaius
            $this->_logger->error($exception->getMessage());
        }
    }
}
