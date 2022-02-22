<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Parser;

/**
 * Response wrapper implementation.
 */
abstract class AbstractResponseParser implements ResponseParserInterface
{
    /**
     * Response XML string.
     *
     * @var string
     */
    private $xml = null;

    /**
     * Root XML response node.
     *
     * @var \SimpleXMLElement
     */
    protected $rootNode = null;

    /**
     * Main transaction XML node.
     *
     * @var \SimpleXMLElement
     */
    private $transactionNode = null;

    /**
     * Init response object.
     *
     * @param string $xml
     */
    public function __construct($xml)
    {
        $this->xml = $xml;
    }

    /**
     * Get root node.
     *
     * @return \SimpleXMLElement
     */
    private function getRootNode()
    {
        if ($this->rootNode === null) {
            $this->rootNode = simplexml_load_string($this->toXml());
        }

        return $this->rootNode;
    }

    /**
     * Get transaction node.
     *
     * @return \SimpleXMLElement
     */
    private function getTransactionNode()
    {
        if ($this->transactionNode === null) {
            $transactionName = $this->getPathPrefix();
            $this->transactionNode =
                $transactionName ? $this->getRootNode()->{$transactionName} : $this->getRootNode();
        }

        return $this->transactionNode;
    }

    /**
     * Get XML string.
     *
     * @return string
     */
    public function toXml()
    {
        return $this->xml;
    }

    /**
     * Render XML string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toXml();
    }

    /**
     * Get response transaction name.
     *
     * @return string
     */
    abstract public function getPathPrefix();

    /**
     * Get response root attribute by key
     *
     * @param string $key
     * @return string
     */
    public function getRootAttribute($key)
    {
        $value = (string) $this->getRootNode()->attributes()->{$key};
        return $value;
    }

    /**
     * Get response data by key.
     *
     * @param string $key
     * @return string
     */
    public function getValue($key)
    {
        $value = (string) $this->searchNodeByPath($key);
        return $value;
    }

    /**
     * @param string $path
     * @return \SimpleXMLElement
     */
    private function searchNodeByPath($path)
    {
        $result = $this->getTransactionNode();
        $segments = explode('/', $path);
        foreach ($segments as $step) {
            if ($result->{$step} === null) {
                return simplexml_load_string("<$step/>");
            }
            $result = $result->{$step};
        }

        return $result;
    }

    /**
     * Get <tokenResponse> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getTokenResponseNode()
    {
        $tokenResponseNode = $this->getTransactionNode()->tokenResponse;
        if ($tokenResponseNode === null) {
            $tokenResponseNode = simplexml_load_string('<tokenResponse/>');
        }

        return $tokenResponseNode;
    }

    /**
     * Get <fundingSource> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getFundingSourceNode()
    {
        $node = $this->getEnhancedAuthResponseNode()->fundingSource;
        if ($node === null) {
            $node = simplexml_load_string('<fundingSource/>');
        }

        return $node;
    }

    /**
     * Ger card product type.
     *
     * @return string
     */
    public function getCardProductType()
    {
        return (string) $this->getEnhancedAuthResponseNode()->cardProductType;
    }

    /**
     * Get funding source type.
     *
     * @return string
     */
    public function getFundingSourceType()
    {
        return (string) $this->getFundingSourceNode()->type;
    }

    /**
     * Get funding source available balance.
     *
     * @return string
     */
    public function getFundingSourceAvailableBalance()
    {
        return (string) $this->getFundingSourceNode()->availableBalance;
    }

    /**
     * Get funding source reloadable.
     */
    public function getFundingSourceReloadable()
    {
        return (string) $this->getFundingSourceNode()->reloadable;
    }

    /**
     * Get funding source prepaid card type.
     *
     * @return string
     */
    public function getFundingSourcePrepaidCardType()
    {
        return (string) $this->getFundingSourceNode()->prepaidCardType;
    }

    /**
     * Get <fraudResult> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getFraudResultNode()
    {
        $fraudResultNode = $this->getTransactionNode()->fraudResult;
        if ($fraudResultNode === null) {
            $fraudResultNode = simplexml_load_string('<fraudResult/>');
        }

        return $fraudResultNode;
    }

    /**
     * Get advanced fraud results node.
     *
     * @return \SimpleXMLElement
     */
    private function getAdvancedFraudResultsNode()
    {
        $advancedFraudResultsNode = $this->getFraudResultNode()->advancedFraudResults;
        if ($advancedFraudResultsNode === null) {
            $advancedFraudResultsNode = simplexml_load_string('<advancedFraudResults/>');
        }
        return $advancedFraudResultsNode;
    }

    /**
     * Get <enhancedAuthResponse> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getEnhancedAuthResponseNode()
    {
        $enhancedAuthResponseNode = $this->getTransactionNode()->enhancedAuthResponse;
        if ($enhancedAuthResponseNode === null) {
            $enhancedAuthResponseNode = simplexml_load_string('<enhancedAuthResponse/>');
        }

        return $enhancedAuthResponseNode;
    }

    /**
     * Get <virtualGiftCardResponse> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getVirtualGiftCardResponseNode()
    {
        $node = $this->getTransactionNode()->virtualGiftCardResponse;
        if ($node === null) {
            $node = simplexml_load_string('<virtualGiftCardResponse/>');
        }

        return $node;
    }

    /**
     * Get <giftCardResponse> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getGiftCardResponseNode()
    {
        $node = $this->getTransactionNode()->giftCardResponse;
        if ($node === null) {
            $node = simplexml_load_string('<giftCardResponse/>');
        }

        return $node;
    }

    /**
     * Get <accountUpdater> node XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getAccountUpdaterNode()
    {
        $node = $this->getTransactionNode()->accountUpdater;
        if ($node === null) {
            $node = simplexml_load_string('<accountUpdater/>');
        }

        return $node;
    }

    /**
     * Get <originalCardTokenInfo> XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getOriginalCardTokenInfoNode()
    {
        $node = $this->getAccountUpdaterNode()->originalCardTokenInfo;
        if ($node === null) {
            $node = simplexml_load_string('<originalCardTokenInfo/>');
        }

        return $node;
    }

    /**
     * Get original token value.
     *
     * @return string
     */
    public function getOriginalCardTokenInfoLitleToken()
    {
        $value = (string) $this->getOriginalCardTokenInfoNode()->litleToken;
        return $value;
    }

    /**
     * Get <newCardTokenInfo> XML container.
     *
     * @return \SimpleXMLElement
     */
    private function getNewCardTokenInfo()
    {
        $node = $this->getAccountUpdaterNode()->newCardTokenInfo;
        if ($node === null) {
            $node = simplexml_load_string('<newCardTokenInfo/>');
        }

        return $node;
    }

    /**
     * Get new token value.
     *
     * @return string
     */
    public function getNewCardTokenInfoLitleToken()
    {
        $value = (string) $this->getNewCardTokenInfo()->litleToken;
        return $value;
    }

    /**
     * New token type.
     *
     * @return string
     */
    public function getNewCardTokenInfoType()
    {
        $value = (string) $this->getNewCardTokenInfo()->type;
        return $value;
    }

    /**
     * New token expiration date.
     *
     * @return string
     */
    public function getNewCardTokenInfoExpDate()
    {
        $value = (string) $this->getNewCardTokenInfo()->expDate;
        return $value;
    }

    /**
     * Get token response code.
     *
     * @return string
     */
    public function getTokenResponseCode()
    {
        $value = (string) $this->getTokenResponseNode()->tokenResponseCode;
        return $value;
    }

    /**
     * Get token response message.
     *
     * @return string
     */
    public function getTokenMessage()
    {
        $value = (string) $this->getTokenResponseNode()->tokenMessage;
        return $value;
    }

    /**
     * Get litle token value.
     *
     * @return string
     */
    public function getLitleToken()
    {
        $value = (string) $this->getTokenResponseNode()->litleToken;
        return $value;
    }

    /**
     * Get Litle token type.
     *
     * @return string
     */
    public function getLitleTokenType()
    {
        $value = (string) $this->getTokenResponseNode()->type;
        return $value;
    }

    /**
     * Get Litle token bin.
     *
     * @return string
     */
    public function getLitleTokenBin()
    {
        $value = (string) $this->getTokenResponseNode()->bin;
        return $value;
    }

    /**
     * Get response code.
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->getValue('response');
    }

    /**
     * Get response message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getValue('message') ? $this->getValue('message') : $this->getRootAttribute('message');
    }

    /**
     * Get Litle transacrtion ID.
     *
     * @return string
     */
    public function getLitleTxnId()
    {
        return $this->getValue('litleTxnId');
    }

    /**
     * Get <responseTime> value.
     *
     * @return string
     */
    public function getResponseTime()
    {
        return $this->getValue('responseTime');
    }

    /**
     * Get advanced fraud review status.
     *
     * @return string
     */
    public function getDeviceReviewStatus()
    {
        return (string) $this->getAdvancedFraudResultsNode()->deviceReviewStatus;
    }

    /**
     * Get advanced fraud reputation score.
     *
     * @return string
     */
    public function getDeviceReputationScore()
    {
        return (string) $this->getAdvancedFraudResultsNode()->deviceReputationScore;
    }

    /**
     * Get AVS result code.
     *
     * @return string
     */
    public function getAvsResult()
    {
        return (string) $this->getFraudResultNode()->avsResult;
    }

    /**
     * Get advanced AVS result.
     *
     * @return string
     */
    public function getAdvancedAvsResult()
    {
        return (string) $this->getFraudResultNode()->advancedAVSResult;
    }

    /**
     * Get card validation result.
     *
     * @return string
     */
    public function getCardValidationResult()
    {
        return (string) $this->getFraudResultNode()->cardValidationResult;
    }

    /**
     * Get issuer country.
     *
     * @return string
     */
    public function getIssuerCountry()
    {
        return (string) $this->getEnhancedAuthResponseNode()->issuerCountry;
    }

    /**
     * Get affluence.
     *
     * @return string
     */
    public function getAffluence()
    {
        return (string) $this->getEnhancedAuthResponseNode()->affluence;
    }

    /**
     * Get Virtual Gift Card Number.
     *
     * @return string
     */
    public function getVirtualGiftCardNumber()
    {
        return (string) $this->getVirtualGiftCardResponseNode()->accountNumber;
    }

    /**
     * Get Gift Card Balance.
     *
     * @return string
     */
    public function getGiftCardBalance()
    {
        return (string) $this->getGiftCardResponseNode()->availableBalance;
    }

    /**
     * Get <authCode> node value.
     *
     * @return string
     */
    public function getAuthCode()
    {
        return trim($this->getValue('authCode'));
    }

    /**
     * Get array of transaction data.
     *
     * @return string[]
     */
    public function toTransactionRawDetails()
    {
        $data = [
            'litleTxnId' => $this->getLitleTxnId(),
            'response' => $this->getResponse(),
            'responseTime' => $this->getResponseTime(),
            'message' => $this->getMessage(),
            'authCode' => $this->getAuthCode(),

            /*
             * Fraud data.
             */
            'avsResult' => $this->getAvsResult(),
            'advancedAvsResult' => $this->getAdvancedAvsResult(),
            'cardValidationResult' => $this->getCardValidationResult(),
            'deviceReviewStatus' => $this->getDeviceReviewStatus(),
            'deviceReputationScore' => $this->getDeviceReputationScore(),

            /*
             * Enhanced data.
             */
            'issuerCountry' => $this->getIssuerCountry(),
            'affluence' => $this->getAffluence(),

            /*
             * Funding source data.
             */
            'cardProductType' => $this->getCardProductType(),
            'fundingSourceType' => $this->getFundingSourceType(),
            'fundingSourceAvailableBalance' => $this->getFundingSourceAvailableBalance(),
            'fundingSourceReloadable' => $this->getFundingSourceReloadable(),
            'fundingSourcePrepaidCardType' => $this->getFundingSourcePrepaidCardType(),

            /*
             * Token data.
             */
            'tokenType' => $this->getLitleTokenType(),
            'tokenBin' => $this->getLitleTokenBin(),
        ];

        return $data;
    }

    /**
     * Get recurringResponse node
     *
     * @return \SimpleXMLElement
     */
    private function getRecurringResponseNode()
    {
        $tokenResponseNode = $this->getTransactionNode()->recurringResponse;
        if ($tokenResponseNode === null) {
            $tokenResponseNode = simplexml_load_string('<recurringResponse/>');
        }

        return $tokenResponseNode;
    }

    /**
     * Get subscriptionId node value from recurringResponse
     *
     * @return string
     */
    public function getRecurringResponseSubscriptionId()
    {
        return (string)$this->getRecurringResponseNode()->subscriptionId;
    }
}
