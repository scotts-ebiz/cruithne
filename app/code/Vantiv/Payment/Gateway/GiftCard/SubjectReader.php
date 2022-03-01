<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard;

use Vantiv\Payment\Gateway\Common\SubjectReader as CommonSubjectReader;

/**
 * Subject reader.
 */
class SubjectReader extends CommonSubjectReader
{
    /**
     * Extract giftCardAccount from command subject.
     *
     * @param array $subject
     * @throws \InvalidArgumentException
     * @return \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    public function readGiftCardAccount(array $subject)
    {
        $giftCardAccountObject = null;

        if (array_key_exists('giftCardAccount', $subject)) {
            $giftCardAccountObject = $subject['giftCardAccount'];
        } else {
            throw new \InvalidArgumentException('giftCardAccount is not set.');
        }

        return $giftCardAccountObject;
    }
}
