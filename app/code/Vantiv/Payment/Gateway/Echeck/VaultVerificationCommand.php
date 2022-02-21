<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Echeck\Builder\EcheckVerificationBuilder as Builder;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckVerificationResponseParserFactory as ParserFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Echeck\Helper\UpdateTokenHelper;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;

/**
 * Verification command implementation.
 */
class VaultVerificationCommand extends AbstractPaymentCommand
{
    /**
     * Token helper.
     *
     * @var UpdateTokenHelper
     */
    private $updateTokenHelper = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param Builder $builder
     * @param Reader $reader
     * @param ParserFactory $parserFactory
     * @param UpdateTokenHelper $updateTokenHelper
     */
    public function __construct(
        HttpClient $client,
        Builder $builder,
        Reader $reader,
        ParserFactory $parserFactory,
        UpdateTokenHelper $updateTokenHelper
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);

        $this->updateTokenHelper = $updateTokenHelper;
    }

    /**
     * Get token helper.
     *
     * @return UpdateTokenHelper
     */
    private function getUpdateTokenHelper()
    {
        return $this->updateTokenHelper;
    }

    /**
     * Execute command.
     *
     * @throws CommandException
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        $this->getUpdateTokenHelper()->execute($subject);

        $countryId = $this->getReader()->readOrderAdapter($subject)->getBillingAddress()->getCountryId();
        if ($countryId === 'US') {
            parent::execute($subject);
        } else {
            $statusHistoryComment = (string) __('Bank account verification skipped.');
            $this->getReader()
                ->readPayment($subject)
                ->getOrder()
                ->addStatusHistoryComment($statusHistoryComment);
        }
    }

    /**
     * Handle response.
     *
     * @param array $subject
     * @param ResponseParserInterface $parser
     * @throws CommandException
     */
    protected function handle(array $subject, ResponseParserInterface $parser)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        if ($parser->getResponse() === ResponseParserInterface::PAYMENT_APPROVED
            || $method->getConfigData('accept_on_fail')
        ) {
            $this->getReader()
                ->readPayment($subject)
                ->setIsTransactionClosed(false)
                ->setTransactionId($parser->getLitleTxnId());

            $this->getReader()
                ->readPayment($subject)
                ->getOrder()
                ->addStatusHistoryComment($parser->getMessage());

            $this->getReader()
                ->readPayment($subject)
                ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $parser->toTransactionRawDetails());
        } else {
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }
    }
}
