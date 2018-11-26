<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Handler;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;
use Vantiv\Payment\Model\GiftCardAccount\PoolFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Payment\Gateway\Command\CommandException;

/**
 * Handle Gift Card status response data.
 */
class StatusResponseHandler
{
    /**
     * Gift Card Account Pool Factory
     *
     * @var PoolFactory
     */
    private $poolFactory = null;

    /**
     * Order Repository
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Valid response codes
     *
     * @var array
     */
    private $validCodes = ['218'];

    /**
     * Invalid response codes
     *
     * @var array
     */
    private $invalidCodes = ['219', '217', '000'];

    /**
     * Constructor.
     *
     * @param PoolFactory $poolFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        PoolFactory $poolFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->poolFactory = $poolFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Retrieve pool model instance
     *
     * @return \Magento\GiftCardAccount\Model\Pool\AbstractPool
     */
    public function getPoolModel()
    {
        return $this->poolFactory->create();
    }

    /**
     * Save Invalid Gift Card Account Code as used in Code Pool
     * in order to avoid its usage in future
     *
     * @param array $subject
     * @param Parser $parser
     * @throws CommandException
     */
    public function handle(array $subject, Parser $parser)
    {
        if (in_array($parser->getResponse(), $this->validCodes)) {
            return;
        } elseif (in_array($parser->getResponse(), $this->invalidCodes)
            && array_key_exists('giftcard_code', $subject)) {
            $this->getPoolModel()
                ->setId($subject['giftcard_code'])
                ->setStatus(\Magento\GiftCardAccount\Model\Pool\AbstractPool::STATUS_USED)
                ->save();
            $message = 'Gift Card code from the Pool was not valid. Please try again.';
        } else {
            $message = $parser->getMessage();
        }

        throw new CommandException(__('We can\'t save the invoice right now. ' . $message));
    }
}
